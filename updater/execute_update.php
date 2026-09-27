<?php
/**
 * WBCE Updater - Execute Update
 *
 * Entpackt das Update-Paket und startet den WBCE Update-Prozess
 * Ersetzt das externe wbce_update_unzip.php Script
 *
 * @category    module
 * @package     updater
 * @version     1.0.2
 * @author      WBCE Community
 * @copyright   2026 WBCE Community
 * @license     MIT License
 */

// Include WBCE framework
$configFile = dirname(dirname(dirname(__FILE__))) . '/config.php';
if (!file_exists($configFile)) {
    die('Configuration file not found');
}
require $configFile;
require_once WB_PATH . '/framework/Admin.php';

// Load central configuration
require_once __DIR__ . '/config_defaults.php';

// Include compatibility checker for dynamic PHP version check
require_once __DIR__ . '/compatibility_checker.php';
require_once __DIR__ . '/update_transaction.php';
require_once __DIR__ . '/archive_validator.php';
require_once __DIR__ . '/UpdateLog.php';

$admin = new admin('Admintools', 'admintools', false, false);

if (!$admin->is_authenticated() || !$admin->isAdmin()) {
    header('Location: ' . ADMIN_URL . '/index.php');
    exit;
}

// Consume the one-time authorization while the PHP session is still open.
// It must be persisted before the long archive operation releases the lock.
$lang = (file_exists(__DIR__ . '/languages/' . LANGUAGE . '.php'))
    ? __DIR__ . '/languages/' . LANGUAGE . '.php'
    : __DIR__ . '/languages/EN.php';
require $lang;
if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); header('Allow: POST'); exit($LANG['ERROR_METHOD_NOT_ALLOWED']);
}
$targetVersion = isset($_POST['version']) && is_scalar($_POST['version']) ? trim((string)$_POST['version']) : '';
$updateToken = isset($_POST['update_token']) && is_scalar($_POST['update_token']) ? (string)$_POST['update_token'] : '';
$embedded = isset($_POST['embedded']) && (string)$_POST['embedded'] === '1';
if (!empty($targetVersion) && !preg_match('/^v?\d+\.\d+(?:\.\d+)?(?:[-+][0-9A-Za-z][0-9A-Za-z.-]*)?$/', $targetVersion)) {
    http_response_code(400); exit(sprintf($LANG['AJAX_VERSION_INVALID'], htmlspecialchars($targetVersion, ENT_QUOTES, 'UTF-8')));
}
$zipFile = WB_PATH . '/wbceup.zip';
$updaterTransaction=updater_consume_transaction($updateToken, $zipFile, $targetVersion);
if (!$updaterTransaction) {
    http_response_code(403); exit($LANG['ERROR_UPDATE_AUTH_INVALID']);
}

// The CMS files are replaced in the next step. Preserve the authenticated
// session independently so install/update.php can safely resume it even when
// the updated session implementation changes during this request.
try {
    $sessionRecoveryToken = bin2hex(random_bytes(32));
} catch (Throwable $exception) {
    $sessionRecoveryToken = hash('sha256', uniqid('wbce-updater-', true));
}
$sessionRecoveryPath = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'wbce-updater-session-' . $sessionRecoveryToken . '.json';
$sessionRecoveryData = json_encode(array('session_id' => session_id(), 'session_name' => session_name(), 'session' => $_SESSION, 'rollback_token' => $updateToken), JSON_INVALID_UTF8_SUBSTITUTE);
if (!is_string($sessionRecoveryData) || @file_put_contents($sessionRecoveryPath, $sessionRecoveryData, LOCK_EX) === false) {
    http_response_code(500);
    exit('Die Administrator-Sitzung konnte für das Update nicht gesichert werden.');
}
@chmod($sessionRecoveryPath, 0600);
// Send the one-time recovery cookie while headers are still available. The
// update response streams a long progress document, so setting it later in
// install/update.php is too late on many PHP/FastCGI installations. The
// dashboard consumes and immediately deletes this short-lived HttpOnly token.
@setcookie('WBCE-updater-recovery', $sessionRecoveryToken, array(
    'expires' => time() + 900,
    'path' => '/',
    'secure' => (defined('DOMAIN_PROTOCOLL') ? DOMAIN_PROTOCOLL : 'https') === 'https',
    'httponly' => true,
    'samesite' => 'Lax',
));
// The snapshot is now durable. Release the original session lock before the
// long archive operation so progress polling stays authenticated and responsive.
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

if ($embedded && strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest') {
    header('Cache-Control: no-store');
    header('X-Accel-Buffering: no');
}

// Persist independent progress events. Reverse proxies may buffer the streaming
// response, while the authenticated overlay can still poll this local file.
$updaterProgressPath = WB_PATH . '/temp/.wbce-updater-progress-' . hash('sha256', $updateToken) . '.ndjson';
@file_put_contents($updaterProgressPath, '', LOCK_EX);
@chmod($updaterProgressPath, 0600);
$updaterProgressSequence = 0;
updater_update_log($targetVersion, 'Information', 'Update-Ausführung', 'Dateiaustausch wird gestartet.');

// Start output
?>
<!DOCTYPE html>
<html lang="<?php echo LANGUAGE; ?>">
<head>
    <meta charset="utf-8">
    <title><?php echo $LANG['TOOL_NAME']; ?> - <?php echo $LANG['EXEC_TITLE']; ?></title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            max-width: 800px;
            margin: 40px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        body.embedded { max-width:none; margin:0; padding:0; background:#f3f6f8; color:#17233b; }
        body.embedded .container { min-height:100%; padding:18px; border-radius:0; background:#f3f6f8; box-shadow:none; }
        body.embedded .container > h1 { display:none; }
        body.embedded .progress { margin:0; }
        body.embedded h2 { margin:0 0 14px; padding:13px 15px; border:1px solid #cdd8e1; border-radius:8px; background:#fff; color:#17233b; font-size:17px; }
        body.embedded .success { border-left:4px solid #16834a; color:#16834a; }
        body.embedded .step { position:relative; margin:0 0 8px; padding:12px 14px 12px 18px; border:1px solid #d5dfe7; border-left:4px solid #2271b1; border-radius:7px; background:#fff; color:#17233b; box-shadow:none; }
        body.embedded .step strong { display:block; margin-bottom:5px; color:#17233b; }
        body.embedded .step span[style*="28a745"] { color:#16834a !important; }
        body.embedded .step span[style*="856404"] { color:#8a6200 !important; }
        body.embedded .step span[style*="dc3545"] { color:#b42318 !important; }
        body.embedded .step:has(span[style*="28a745"]) { border-left-color:#16834a; }
        body.embedded .step:has(span[style*="28a745"]) strong::before { content:'✓'; display:inline-grid; place-items:center; width:1.15rem; height:1.15rem; margin-right:.45rem; border-radius:50%; background:#16834a; color:#fff; font-size:.78rem; }
        body.embedded .step:has(span[style*="856404"]) { border-left-color:#d79b00; }
        body.embedded .error { margin:0; border-radius:7px; }
        body.embedded .file-action { margin:4px 0 0; padding:6px 9px; border-left:3px solid #16834a; border-radius:4px; background:#fff; color:#17233b; font:12px/1.4 ui-monospace,SFMono-Regular,Menlo,monospace; overflow-wrap:anywhere; }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .success {
            color: #28a745;
            font-size: 24px;
            margin-bottom: 20px;
        }
        .error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
        }
        .info-box {
            background: #e7f3ff;
            border: 1px solid #b8daff;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
            margin-top: 20px;
        }
        .button:hover {
            background: #0056b3;
        }
        .progress {
            margin: 20px 0;
        }
        .step {
            padding: 10px;
            margin: 5px 0;
            background: #f8f9fa;
            border-left: 4px solid #6c757d;
        }
        .step.success {
            border-left-color: #28a745;
            background: #d4edda;
        }
        .step.error {
            border-left-color: #dc3545;
            background: #f8d7da;
        }
        .running-overlay {
            position: fixed;
            inset: 0;
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            background:
                radial-gradient(circle at 18% 18%, rgba(80, 167, 226, .36), transparent 34%),
                radial-gradient(circle at 82% 76%, rgba(41, 114, 167, .32), transparent 36%),
                linear-gradient(135deg, #102435, #1d425b);
        }
        .running-overlay[hidden] { display: none; }
        .running-card {
            width: min(420px,calc(100% - 40px));
            box-sizing: border-box;
            display: flex;
            align-items: center;
            flex-direction: column;
            padding: 36px 32px;
            border-radius: 10px;
            background: #fff;
            color: var(--wbce-text-color, #1d2327);
            box-shadow: 0 16px 45px rgba(0,0,0,.35);
            text-align: center;
        }
        .running-overlay::before,
        .running-overlay::after {
            position: absolute;
            border: 1px solid rgba(255,255,255,.16);
            border-radius: 12px;
            background: rgba(255,255,255,.08);
            content: '';
            transform: rotate(-8deg);
        }
        .running-overlay::before { width: 28rem; height: 15rem; left: -8rem; bottom: -4rem; }
        .running-overlay::after { width: 22rem; height: 12rem; right: -6rem; top: -4rem; transform: rotate(11deg); }
        .running-card { position: relative; z-index: 1; }
        .running-spinner {
            width: 52px;
            height: 52px;
            border: 5px solid #dfe7ee;
            border-top-color: var(--wbce-accent, #2271b1);
            border-radius: 50%;
            animation: running-spin .8s linear infinite;
        }
        .running-overlay strong { margin-top: 18px; font-size: 18px; }
        .running-overlay span { margin-top: 8px; color: #50575e; }
        @keyframes running-spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body class="<?php echo $embedded ? 'embedded' : ''; ?>">
    <?php if (!$embedded): ?>
    <div id="running-overlay" class="running-overlay" aria-live="assertive" aria-busy="true">
        <div class="running-card">
            <div class="running-spinner" aria-hidden="true"></div>
            <strong>⏳ <?php echo $LANG['UPDATE_RUNNING']; ?>...</strong>
            <span><?php echo $LANG['UPDATE_RUNNING_HINT']; ?></span>
        </div>
    </div>
    <?php endif; ?>
    <div class="container">
        <h1>🚀 <?php echo $LANG['TOOL_NAME']; ?></h1>
        <h2><?php echo $LANG['EXEC_TITLE']; ?></h2>

        <div class="progress">
<?php

// Send visible progress before the potentially lengthy archive operation.
// The embedded frame receives each copied file and action as it happens.
function updater_execute_flush(): void
{
    static $responseStarted = false;
    if (!$responseStarted) {
        echo str_repeat(' ', 4096);
        $responseStarted = true;
    }
    @ob_flush();
    @flush();
}
function updater_execute_progress(int $percent, string $message, string $kind = 'step'): void
{
    global $updaterProgressPath, $updaterProgressSequence, $targetVersion;
    if ($kind !== 'file') updater_update_log($targetVersion, 'Information', 'Update-Ausführung', $message);
    // The browser reads the executing response as a stream. Emit file events
    // there ten times per second, while keeping the durable fallback log at
    // four writes per second so its lock never slows down extraction.
    static $lastFileStream = 0.0;
    static $lastFilePersist = 0.0;
    $now = microtime(true);
    $persistEvent = true;
    if ($kind === 'file') {
        if (($now - $lastFileStream) < 0.10) return;
        $lastFileStream = $now;
        $persistEvent = ($now - $lastFilePersist) >= 0.25;
        if ($persistEvent) $lastFilePersist = $now;
    }
    $event = array('type'=>'wbce-updater-progress', 'id'=>++$updaterProgressSequence, 'percent'=>max(0,min(100,$percent)), 'message'=>$message, 'kind'=>$kind);
    // Persist a compact fallback stream for reconnects; live output is sent
    // directly to the browser below without waiting for this file operation.
    if ($persistEvent) @file_put_contents($updaterProgressPath, json_encode($event, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n", FILE_APPEND | LOCK_EX);
    $payload = json_encode($event, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    // The marker is consumed by the asynchronous updater UI. Unlike HTML
    // fragments it remains intact even when PHP, FastCGI or the browser split
    // a response in the middle of a tag.
    echo "\n<!--WBCE_UPDATER_EVENT " . base64_encode($payload) . "-->\n";
    echo '<script>try{window.parent.postMessage('.$payload.',window.location.origin)}catch(e){}</script>';
    updater_execute_flush();
}
$updaterExecuteCompletionSent = false;
function updater_execute_completion(bool $success, string $sessionRecoveryToken, string $message = ''): void
{
    global $updaterExecuteCompletionSent, $targetVersion;
    updater_update_log($targetVersion, $success ? 'Success' : 'Error', 'Update-Ausführung', $success ? 'Dateiaustausch erfolgreich abgeschlossen.' : ($message !== '' ? $message : 'Update-Ausführung fehlgeschlagen.'));
    $updaterExecuteCompletionSent = true;
    global $updaterProgressPath, $updaterProgressSequence;
    $event = array(
        'type' => $success ? 'wbce-updater-complete' : 'wbce-updater-error',
        'id' => ++$updaterProgressSequence,
        'recovery_token' => $sessionRecoveryToken,
        'message' => $message,
    );
    @file_put_contents($updaterProgressPath, json_encode($event, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n", FILE_APPEND | LOCK_EX);
    $payload = json_encode($event, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    echo "\n<!--WBCE_UPDATER_EVENT " . base64_encode($payload) . "-->\n";
    updater_execute_flush();
}
register_shutdown_function(function () use ($sessionRecoveryToken): void {
    global $updaterExecuteCompletionSent;
    if ($updaterExecuteCompletionSent) {
        return;
    }
    $lastError = error_get_last();
    $detail = 'Die Verbindung wurde vor dem Abschluss der Update-Ausführung beendet.';
    if (is_array($lastError) && !empty($lastError['message'])) {
        $detail .= ' PHP-Fehler in ' . basename((string)($lastError['file'] ?? 'unbekannt'))
            . ':' . (int)($lastError['line'] ?? 0) . ' – ' . (string)$lastError['message'];
    }
    updater_execute_completion(false, $sessionRecoveryToken, $detail);
});
ignore_user_abort(true); @set_time_limit(0);
@ini_set('output_buffering', '0');
@ini_set('zlib.output_compression', '0');
updater_execute_flush();

$success = true;
$errors = [];
$warnings = [];
$changeLog = '';

// Step 1: PHP compatibility check for the selected target version. A known
// incompatibility must stop before files are replaced; otherwise the updated
// CMS could become inaccessible immediately after the update.
echo '<div class="step">';
echo '<strong>' . $LANG['EXEC_STEP1'] . '</strong><br>'; updater_execute_progress(5, $LANG['EXEC_STEP1']);

if (!empty($targetVersion)) {
    $compatibility = checkPhpCompatibility($targetVersion, PHP_VERSION);

    if (!$compatibility['compatible']) {
        $phpMin = $compatibility['details']['php_min'] ?? null;
        $phpMax = $compatibility['details']['php_max'] ?? null;

        if ($phpMin === null && $phpMax === null) {
            $detailMsg = $compatibility['details']['error'] ?? 'Keine Anforderungsdaten verfügbar';
            echo '<span style="color: #856404;">⚠️ ' . sprintf($LANG['EXEC_PHP_CANNOT_CHECK'], htmlspecialchars($detailMsg)) . '</span>';
        } else {
            $phpMinStr      = $phpMin ?? '?';
            $phpMaxStr      = $phpMax ?? '?';
            $phpRecommended = $compatibility['details']['php_recommended'] ?? $phpMinStr;

            echo '<span style="color: #dc3545; font-weight: bold;">⚠️ ' . $LANG['EXEC_PHP_INCOMPAT'] . '</span><br>';
            echo '<span style="color: #856404;">';
            echo $LANG['EXEC_PHP_CURRENT'] . ' <strong>' . PHP_VERSION . '</strong><br>';
            echo sprintf($LANG['EXEC_PHP_REQUIRED_FOR'], htmlspecialchars($targetVersion)) . ' <strong>' . htmlspecialchars($phpMinStr) . ' - ' . htmlspecialchars($phpMaxStr) . '</strong><br>';
            echo $LANG['EXEC_PHP_RECOMMENDED'] . ' <strong>' . htmlspecialchars($phpRecommended) . '</strong><br><br>';
            echo '<em>' . $LANG['EXEC_PHP_CONTINUE_HINT'] . '</em>';
            echo '</span>';

            $warnings[] = sprintf($LANG['EXEC_PHP_COMPAT_WARN'], PHP_VERSION, $targetVersion, $phpMinStr, $phpMaxStr);
            $errors[] = sprintf($LANG['EXEC_PHP_COMPAT_WARN'], PHP_VERSION, $targetVersion, $phpMinStr, $phpMaxStr);
            $success = false;
        }
    } else {
        echo '<span style="color: #28a745;">✅ ' . sprintf($LANG['EXEC_PHP_COMPATIBLE_MSG'], PHP_VERSION, htmlspecialchars($targetVersion)) . '</span>';

        if (isset($compatibility['details']['warning']) && !empty($compatibility['details']['warning'])) {
            echo '<br><span style="color: #856404;">⚠️ ' . htmlspecialchars($compatibility['details']['warning']) . '</span>';
        }
    }
} else {
    echo '<span style="color: #856404;">⚠️ ' . $LANG['EXEC_PHP_SKIPPED'] . '</span>';
}

echo '</div>';
updater_execute_flush();

// Step 2: Check if ZIP file exists
if ($success) {
    echo '<div class="step">';
    echo '<strong>' . $LANG['EXEC_STEP2'] . '</strong><br>'; updater_execute_progress(12, $LANG['EXEC_STEP2']);

    if (!file_exists($zipFile)) {
        $success  = false;
        $error    = $LANG['EXEC_ZIP_MISSING'];
        $errors[] = $error;
        $rolledBack=updater_rollback_restore($updateToken);
        $errors[]=$rolledBack ? 'Dateien wurden vollständig zurückgesetzt.' : 'Der automatische Datei-Rollback ist fehlgeschlagen.';
        echo '<span style="color: #dc3545;">❌ ' . htmlspecialchars($error) . '</span>';
    } else {
        $fileSize = filesize($zipFile);
        echo '<span style="color: #28a745;">✅ ' . sprintf($LANG['EXEC_ZIP_FOUND'], round($fileSize / 1024 / 1024, 2)) . '</span>';
    }

    echo '</div>';
}

// Step 3: Extract ZIP
if ($success) {
    echo '<div class="step">';
    echo '<strong>' . $LANG['EXEC_STEP3'] . '</strong><br>'; updater_execute_progress(20, $LANG['EXEC_STEP3']);

    try {
        $zip = new ZipArchive;
        $res = $zip->open($zipFile);

        if ($res === TRUE) {
            updater_execute_progress(21, 'Update-Paket wird geöffnet und geprüft …');
            $archiveValidation = updater_validate_archive($zip, $LANG);
            if (!$archiveValidation['success']) {
                $zip->close();
                throw new Exception($archiveValidation['message']);
            }
            updater_execute_progress(23, 'Sicherheitsprüfung des Update-Pakets abgeschlossen …');
            // Keep the package's release notes for the final confirmation page.
            // They document the concrete changes applied by this update.
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = (string) $zip->getNameIndex($i);
                if (preg_match('~(?:^|/)(?:CHANGELOG|CHANGES)(?:\.md|\.txt)?$~i', $name)) {
                    $notes = $zip->getFromIndex($i);
                    if (is_string($notes) && $notes !== '') {
                        $changeLog = substr($notes, 0, 262144);
                        break;
                    }
                }
            }
            $path         = WB_PATH;
            $numFiles     = $zip->numFiles;
            // CMS release ZIPs are rooted at wbce/. Strip that container so
            // files update WB_PATH itself instead of creating WB_PATH/wbce/.
            $archiveRoot = '';
            $hasCmsRoot = $numFiles > 0;
            for ($i = 0; $i < $numFiles; $i++) {
                if (!str_starts_with(str_replace('\\', '/', (string) $zip->getNameIndex($i)), 'wbce/')) {
                    $hasCmsRoot = false;
                    break;
                }
            }
            if ($hasCmsRoot) $archiveRoot = 'wbce/';
            $relativeName = static function (string $name) use ($archiveRoot): string {
                $name = str_replace('\\', '/', $name);
                return $archiveRoot !== '' ? substr($name, strlen($archiveRoot)) : $name;
            };
            updater_execute_progress(25, 'Dateiliste vorbereitet: ' . $numFiles . ' Einträge werden aktualisiert …');
            $realBasePath = realpath($path);

            if ($realBasePath === false) {
                throw new Exception($LANG['EXEC_DIR_RESOLVE_ERROR']);
            }

            // Delta archives carry explicit removals. Never remove a complete
            // add-on merely because it disappeared from a CMS bundle.
            $deletions=$zip->getFromName('wbce/.wbce-delta-deletions.json');
            if(is_string($deletions))foreach((array)json_decode($deletions,true) as $deleted){if(!is_string($deleted)||!preg_match('#^[A-Za-z0-9._ @()/-]+$#',$deleted)||str_contains($deleted,'..'))throw new RuntimeException('Ungültiger Delta-Löschpfad.');if(preg_match('#^(modules|templates)/([^/]+)/#',$deleted,$m)){ $prefix=$m[1].'/'.$m[2].'/';$stillPresent=false;for($j=0;$j<$numFiles;$j++)if(str_starts_with($relativeName((string)$zip->getNameIndex($j)),$prefix)){$stillPresent=true;break;}if(!$stillPresent)continue;}if(preg_match('#^languages/[^/]+$#',$deleted))continue;$target=$realBasePath.DIRECTORY_SEPARATOR.str_replace('/',DIRECTORY_SEPARATOR,$deleted);if(is_file($target)){updater_rollback_record($updateToken,$deleted,$target);if(!@unlink($target))throw new RuntimeException('Delta-Datei konnte nicht gelöscht werden: '.$deleted);}}

            for ($i = 0; $i < $numFiles; $i++) {
                $stat     = $zip->statIndex($i);
                $filename = $relativeName((string) $stat['name']);
                if ($filename === '') continue;

                if (strpos($filename, '../') !== false || strpos($filename, '..\\') !== false ||
                    strpos($filename, "\0") !== false) {
                    $zip->close();
                    throw new Exception(sprintf($LANG['EXEC_SEC_BAD_PATH'], htmlspecialchars($filename)));
                }

                if (substr($filename, 0, 1) === '/' || (strlen($filename) >= 2 && $filename[1] === ':')) {
                    $zip->close();
                    throw new Exception(sprintf($LANG['EXEC_SEC_ABS_PATH'], htmlspecialchars($filename)));
                }

                $fullPath    = $realBasePath . DIRECTORY_SEPARATOR
                    . ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $filename), DIRECTORY_SEPARATOR);
                $resolvedDir = realpath(dirname($fullPath));

                if ($resolvedDir !== false) {
                    if (strncmp($resolvedDir . DIRECTORY_SEPARATOR,
                                $realBasePath . DIRECTORY_SEPARATOR,
                                strlen($realBasePath) + 1) !== 0) {
                        $zip->close();
                        throw new Exception($LANG['EXEC_SEC_TRAVERSAL']);
                    }
                } else {
                    if (strncmp($fullPath,
                                $realBasePath . DIRECTORY_SEPARATOR,
                                strlen($realBasePath) + 1) !== 0) {
                        $zip->close();
                        throw new Exception($LANG['EXEC_SEC_TRAVERSAL']);
                    }
                }
            }

            if (!updater_rollback_begin($updateToken)) throw new RuntimeException('Wiederherstellungspunkt konnte nicht angelegt werden.');
            updater_execute_progress(24, 'Wiederherstellungspunkt wird angelegt …');
            updater_execute_progress(25, 'Dateien werden aktualisiert …');
            $copiedFiles = 0;
            for ($i = 0; $i < $numFiles; $i++) {
                $zipFilename = (string) $zip->getNameIndex($i);
                $filename = $relativeName($zipFilename);
                if ($filename === '') continue;
                if ($filename === '.wbce-delta-deletions.json') continue;
                $entryStat = $zip->statIndex($i);
                $relative = ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $filename), DIRECTORY_SEPARATOR);
                $destination = $realBasePath . DIRECTORY_SEPARATOR . $relative;

                // Extracting selected entries with ZipArchive::extractTo() is
                // unreliable on some PHP builds. Stream each validated entry
                // explicitly, so a directory entry cannot interrupt step 3.
                if (substr($filename, -1) === '/') {
                    if (!is_dir($destination) && !@mkdir($destination, 0755, true) && !is_dir($destination)) {
                        throw new RuntimeException($LANG['EXEC_EXTRACT_WRITE_FAILED'] . ': ' . $filename);
                    }
                    continue;
                }
                $targetDirectory = dirname($destination);
                if (!is_dir($targetDirectory) && !@mkdir($targetDirectory, 0755, true) && !is_dir($targetDirectory)) {
                    throw new RuntimeException($LANG['EXEC_EXTRACT_WRITE_FAILED'] . ': ' . $filename);
                }
                $resolvedTargetDirectory = realpath($targetDirectory);
                if ($resolvedTargetDirectory === false || strncmp($resolvedTargetDirectory . DIRECTORY_SEPARATOR, $realBasePath . DIRECTORY_SEPARATOR, strlen($realBasePath) + 1) !== 0) {
                    throw new RuntimeException($LANG['EXEC_SEC_TRAVERSAL']);
                }
                // A full CMS ZIP contains many unchanged files. Compare its
                // uncompressed CRC with the destination first: this avoids a
                // rollback copy, decompression and write for identical files.
                $entryCrc = isset($entryStat['crc']) ? strtolower(sprintf('%08x', (int)$entryStat['crc'])) : '';
                if ($entryCrc !== '' && is_file($destination) && @hash_file('crc32b', $destination) === $entryCrc) {
                    $copiedFiles++;
                    updater_execute_progress(25 + (int)floor(($copiedFiles / max(1, $numFiles)) * 65), 'Unverändert: ' . $filename, 'file');
                    continue;
                }
                updater_rollback_record($updateToken, str_replace(DIRECTORY_SEPARATOR, '/', $relative), $destination);
                $input = $zip->getStream($zipFilename);
                $output = @fopen($destination, 'wb');
                if (!is_resource($input) || !is_resource($output)) {
                    if (is_resource($input)) fclose($input);
                    if (is_resource($output)) fclose($output);
                    throw new RuntimeException($LANG['EXEC_EXTRACT_WRITE_FAILED'] . ': ' . $filename);
                }
                $written = 0;
                // Copy in chunks so the browser receives a heartbeat even for
                // one very large file. The progress helper itself throttles
                // notifications to keep PHP-FPM fast.
                while (!feof($input)) {
                    $chunk = fread($input, 1048576);
                    if ($chunk === false) { $written = false; break; }
                    if ($chunk === '') continue;
                    $length = strlen($chunk);
                    $offset = 0;
                    while ($offset < $length) {
                        $part = fwrite($output, substr($chunk, $offset));
                        if ($part === false || $part === 0) { $written = false; break 2; }
                        $offset += $part;
                    }
                    $written += $length;
                    updater_execute_progress(25 + (int)floor((($copiedFiles + min(1, $written / max(1, (int)($entryStat['size'] ?? 1)))) / max(1, $numFiles)) * 65), 'Aktualisiere: ' . $filename, 'file');
                }
                fclose($input); fclose($output);
                if ($written === false || (int)$written !== (int)($entryStat['size'] ?? -1)) {
                    throw new RuntimeException($LANG['EXEC_EXTRACT_WRITE_FAILED'] . ': ' . $filename);
                }
                $copiedFiles++;
                // Render a compact live list in batches. The full per-file log
                // remains available through the durable progress endpoint.
                if (($copiedFiles % 25) === 0 || $copiedFiles === $numFiles) {
                    echo '<div class="file-action">✓ ' . htmlspecialchars($filename, ENT_QUOTES, 'UTF-8') . ' (' . $copiedFiles . '/' . $numFiles . ')</div>';
                }
                updater_execute_progress(25 + (int)floor(($copiedFiles / max(1, $numFiles)) * 65), $filename, 'file');
            }
            $zip->close();

            // Ensure that PHP does not keep the previous release's scripts in
            // OPcache after files were replaced in-place. The actual database
            // updater runs in the following request and must see the new
            // admin/interface/version.php immediately.
            if (function_exists('opcache_reset')) {
                @opcache_reset();
            }

            echo '<span style="color: #28a745;">✅ ' . sprintf($LANG['EXEC_FILES_EXTRACTED'], $copiedFiles, htmlspecialchars($path)) . '</span>'; updater_execute_progress(90, sprintf($LANG['EXEC_FILES_EXTRACTED'], $copiedFiles, $path));
        } else {
            throw new Exception($LANG['EXEC_ZIP_OPEN_FAILED']);
        }
    } catch (Throwable $e) {
        $success  = false;
        $error    = sprintf($LANG['EXEC_EXTRACT_FAILED'], $e->getMessage());
        $errors[] = $error;
        echo '<span style="color: #dc3545;">❌ ' . htmlspecialchars($error) . '</span>';
    }

    echo '</div>';
}

// Step 4: Check if install/update.php exists
if ($success) {
    echo '<div class="step">';
    echo '<strong>' . $LANG['EXEC_STEP4'] . '</strong><br>'; updater_execute_progress(93, $LANG['EXEC_STEP4']);

    $updateScript = WB_PATH . '/install/update.php';

    if (!file_exists($updateScript)) {
        $success  = false;
        $error    = $LANG['EXEC_SCRIPT_MISSING'];
        $errors[] = $error;
        echo '<span style="color: #dc3545;">❌ ' . htmlspecialchars($error) . '</span>';
    } else {
        echo '<span style="color: #28a745;">✅ ' . $LANG['EXEC_SCRIPT_FOUND'] . '</span>';
    }

    echo '</div>';
}

// Step 5: Cleanup (delete ZIP)
if ($success) {
    echo '<div class="step">';
    echo '<strong>' . $LANG['EXEC_STEP5'] . '</strong><br>'; updater_execute_progress(97, $LANG['EXEC_STEP5']);

    if (@unlink($zipFile)) {
        echo '<span style="color: #28a745;">✅ ' . $LANG['EXEC_ZIP_DELETED'] . '</span>';
    } else {
        echo '<span style="color: #856404;">⚠️ ' . $LANG['EXEC_ZIP_DELETE_FAILED'] . '</span>';
    }

    echo '</div>';
}

// If preparation failed, do not leave a maintenance mode enabled by this
// updater behind. On success it stays active until install/update.php ends.
if (!$success) {
    require_once __DIR__ . '/maintenance_helper.php';
    updater_disable_own_maintenance();
}

// Keep the exact release notes from the verified archive for the final CMS
// update screen.  The file is deliberately placed outside the web root and
// contains no executable data.
if ($success) updater_execute_progress(100, $LANG['READY_TO_UPDATE']);

if ($success && $changeLog !== '') {
    $changeLogTarget = WB_PATH . '/temp/.wbce-updater-release-notes.txt';
    @file_put_contents($changeLogTarget, $changeLog, LOCK_EX);
    @chmod($changeLogTarget, 0600);
}
// Send a dedicated final marker. The browser must not infer success from the
// trailing HTML because proxies are allowed to truncate or re-chunk it.
updater_execute_completion($success, $sessionRecoveryToken, $success ? '' : implode(' ', $errors));

?>
        </div>

<?php if ($success): ?>
        <h2 class="success">✅ <?php echo $LANG['EXECUTION_RUNNING']; ?></h2>

        <?php if (!empty($warnings)): ?>
        <div class="error" style="background: #fff3cd; border-color: #ffc107; color: #856404;">
            <strong>⚠️ <?php echo $LANG['EXEC_WARNINGS_TITLE']; ?></strong>
            <ul>
                <?php foreach ($warnings as $warning): ?>
                    <li><?php echo htmlspecialchars($warning); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <form action="<?php echo WB_URL; ?>/install/update.php" method="post" id="cms-update-form" hidden>
            <input type="hidden" name="backup_confirmed" value="confirmed">
            <input type="hidden" name="updater_session_recovery" value="<?php echo htmlspecialchars($sessionRecoveryToken, ENT_QUOTES, 'UTF-8'); ?>">
        </form>

<?php else: ?>
        <h2 class="error">❌ <?php echo $LANG['EXEC_ERROR_TITLE']; ?></h2>

        <div class="error">
            <strong><?php echo $LANG['ERROR_OCCURRED']; ?></strong>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>

        <p>
            <a href="<?php echo ADMIN_URL; ?>/admintools/tool.php?tool=updater" class="button">
                ← <?php echo $LANG['BACK_TO_UPDATER']; ?>
            </a>
        </p>
<?php endif; ?>

    </div>
    <script>
    (function () {
        var overlay = document.getElementById('running-overlay');
        var form = document.getElementById('cms-update-form');
        if (form) {
            form.addEventListener('submit', function () {
                if (overlay) {
                    overlay.hidden = false;
                    overlay.querySelector('strong').textContent = '⏳ Update-Prozess wird ausgeführt …';
                }
            });
            // The archive preparation has already completed successfully.
            // Continue directly to the database updater so the progress view
            // remains visible without an intermediate, inactive page.
            form.requestSubmit();
        }
        <?php if (!$success): ?>if (overlay) overlay.hidden = true;<?php endif; ?>
    }());
    </script>
</body>
</html>
