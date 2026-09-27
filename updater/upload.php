<?php
/**
 * Updater - Upload Handler
 *
 * Verarbeitet manuell hochgeladene WBCE ZIP-Dateien und bereitet Update vor
 *
 * @category    module
 * @package     updater
 * @version     1.0.2
 * @author      WBCE Community
 * @copyright   2026 WBCE Community
 * @license     MIT License
 */

// Increase time limits
set_time_limit(300);

// Include WBCE framework
require '../../config.php';
require_once WB_PATH . '/framework/Admin.php';

// Load central configuration
require_once __DIR__ . '/config_defaults.php';

// Include checksum validator
require_once __DIR__ . '/checksum_validator.php';
require_once __DIR__ . '/update_transaction.php';
require_once __DIR__ . '/archive_validator.php';
require_once __DIR__ . '/UpdateLog.php';

// Load language file
$lang = (file_exists(__DIR__ . '/languages/' . LANGUAGE . '.php'))
    ? __DIR__ . '/languages/' . LANGUAGE . '.php'
    : __DIR__ . '/languages/EN.php';
require $lang;

// Security check: Admin only
$admin = new admin('Admintools', 'admintools', false, false);

if (!empty($updater_disabled)) {
    header('Location: ' . ADMIN_URL . '/admintools/tool.php?tool=updater');
    exit;
}

// FTAN Check
if (!$admin->checkFTAN()) {
    header('Location: ' . ADMIN_URL . '/admintools/tool.php?tool=updater');
    exit;
}

// Check if POST data is present
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . ADMIN_URL . '/admintools/tool.php?tool=updater');
    exit;
}

// Get POST parameters
$backup_confirmed = isset($_POST['backup_confirmed_upload']) && $_POST['backup_confirmed_upload'] === '1';
$enable_maintenance = isset($_POST['enable_maintenance_upload']) && $_POST['enable_maintenance_upload'] === '1';
$target_version = isset($_POST['target_version_upload']) && is_scalar($_POST['target_version_upload']) ? trim((string)$_POST['target_version_upload']) : '';
if (!empty($target_version) && !preg_match('/^v?\d+\.\d+(?:\.\d+)?(?:[-+][0-9A-Za-z][0-9A-Za-z.-]*)?$/', $target_version)) {
    $target_version = ''; // Ungültiges Format ignorieren
}

// Validate backup confirmation
if (!$backup_confirmed) {
    header('Location: ' . ADMIN_URL . '/admintools/tool.php?tool=updater');
    exit;
}

// Check write permissions
if (!is_writable(WB_PATH)) {
    header('Location: ' . ADMIN_URL . '/admintools/tool.php?tool=updater');
    exit;
}

// Start process
$errors = [];
$success = true;
$uploaded_checksum = null;
$streaming = (string)($_POST['stream'] ?? '') === '1'
    && strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';

function updater_upload_stream(string $state, string $message, int $percent = 0, array $data = array()): void
{
    global $streaming;
    if (!$streaming) return;
    echo json_encode(array_merge(array('state' => $state, 'message' => $message, 'percent' => $percent), $data), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . "\n";
    @ob_flush();
    @flush();
}

if ($streaming) {
    while (ob_get_level() > 0) @ob_end_flush();
    @ini_set('output_buffering', '0');
    header('Content-Type: application/x-ndjson; charset=utf-8');
    header('Cache-Control: no-store');
    header('X-Accel-Buffering: no');
    updater_upload_stream('prepare', $LANG['LOADING_UPLOAD'], 2);
}

// Step 1: Validate and process uploaded ZIP file
try {
    $chunkUploadId = isset($_POST['chunk_upload_id']) && is_scalar($_POST['chunk_upload_id']) ? (string)$_POST['chunk_upload_id'] : '';
    $chunkedUpload = null;
    if ($chunkUploadId !== '' && preg_match('/^[a-f0-9]{40}$/', $chunkUploadId)) {
        $chunkUploads=updater_transaction_session_get('WBCE_UPDATER_CHUNK_UPLOADS',array());
        $chunkedUpload=is_array($chunkUploads)?($chunkUploads[$chunkUploadId]??null):null;
        if(!is_array($chunkedUpload)){
            $chunkState=WB_PATH.'/temp/updater_chunks/'.$chunkUploadId.'.json';
            $chunkRaw=@file_get_contents($chunkState);$chunkCandidate=is_string($chunkRaw)?json_decode($chunkRaw,true):null;
            if(is_array($chunkCandidate))$chunkedUpload=$chunkCandidate;
        }
    }

    if (is_array($chunkedUpload) && !empty($chunkedUpload['complete'])) {
        $uploaded_file = (string) $chunkedUpload['path'];
        $uploaded_name = (string) $chunkedUpload['name'];
        $uploaded_size = is_file($uploaded_file) ? filesize($uploaded_file) : false;
        $chunkBase = realpath(WB_PATH . '/temp/updater_chunks');
        $chunkFile = realpath($uploaded_file);
        if ($uploaded_size === false || $chunkBase === false || $chunkFile === false || strpos($chunkFile, $chunkBase . DIRECTORY_SEPARATOR) !== 0) {
            throw new Exception($LANG['ERROR_UPLOAD_FAILED']);
        }
        unset($chunkUploads[$chunkUploadId]);
        updater_transaction_session_set('WBCE_UPDATER_CHUNK_UPLOADS',$chunkUploads);
        @unlink(WB_PATH.'/temp/updater_chunks/'.$chunkUploadId.'.json');
        $is_chunked_upload = true;
    } else {
        if (!isset($_FILES['zip_file']) || $_FILES['zip_file']['error'] === UPLOAD_ERR_NO_FILE) {
            throw new Exception($LANG['ERROR_NO_FILE_UPLOADED']);
        }
        switch ($_FILES['zip_file']['error']) {
            case UPLOAD_ERR_OK: break;
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE: throw new Exception($LANG['ERROR_ZIP_TOO_LARGE']);
            case UPLOAD_ERR_PARTIAL: throw new Exception($LANG['ERROR_UPLOAD_PARTIAL']);
            case UPLOAD_ERR_NO_TMP_DIR: throw new Exception($LANG['ERROR_UPLOAD_NO_TMP_DIR']);
            case UPLOAD_ERR_CANT_WRITE: throw new Exception($LANG['ERROR_UPLOAD_CANT_WRITE']);
            default: throw new Exception($LANG['ERROR_UPLOAD_FAILED']);
        }
        $uploaded_file = $_FILES['zip_file']['tmp_name'];
        $uploaded_name = $_FILES['zip_file']['name'];
        $uploaded_size = (int) $_FILES['zip_file']['size'];
        $is_chunked_upload = false;
        if (!is_uploaded_file($uploaded_file)) {
            throw new Exception($LANG['ERROR_UPLOAD_FAILED']);
        }
    }
    updater_upload_stream('validate', 'Datei wird geprüft …', 18);

    // Security: Validate file extension
    $file_extension = strtolower(pathinfo($uploaded_name, PATHINFO_EXTENSION));
    if ($file_extension !== 'zip') {
        throw new Exception(sprintf($LANG['ERROR_ZIP_ONLY'], htmlspecialchars($uploaded_name)));
    }

    // Security: Validate MIME type using finfo (OOP style, PHP 8.5 compatible)
    if (class_exists('finfo')) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime_type = $finfo->file($uploaded_file);

        $allowed_mime_types = [
            'application/zip',
            'application/x-zip',
            'application/x-zip-compressed',
            'application/octet-stream' // Some servers report this for ZIP
        ];

        if (!in_array($mime_type, $allowed_mime_types)) {
            throw new Exception(sprintf($LANG['ERROR_INVALID_MIME_TYPE'], htmlspecialchars($mime_type)));
        }
    }
    updater_upload_stream('validate', 'ZIP-Archiv wird geprüft …', 32);
    if (isset($_FILES['sha_file']) && (int)$_FILES['sha_file']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ((int)$_FILES['sha_file']['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($_FILES['sha_file']['tmp_name'])) throw new Exception($LANG['ERROR_UPLOAD_FAILED']);
        $shaText=trim((string)file_get_contents($_FILES['sha_file']['tmp_name']));
        if (!preg_match('/\b([a-f0-9]{64})\b/i',$shaText,$shaMatch) || !hash_equals(strtolower($shaMatch[1]),hash_file('sha256',$uploaded_file))) throw new Exception($LANG['ERROR_CHECKSUM_MISMATCH']);
    }

    // Security: Check file size (configurable max size)
    if ($uploaded_size > WBCE_UPDATER_MAX_UPLOAD_SIZE) {
        throw new Exception(sprintf($LANG['ERROR_FILE_TOO_LARGE_MB'], round(WBCE_UPDATER_MAX_UPLOAD_SIZE / 1024 / 1024)));
    }


    // Check if file is a valid ZIP
    $zip = new ZipArchive();
    $zip_check = $zip->open($uploaded_file, ZipArchive::CHECKCONS);

    if ($zip_check !== true) {
        throw new Exception($LANG['ERROR_INVALID_ZIP']);
    }
    $archiveValidation = updater_validate_archive($zip, $LANG);
    if (!$archiveValidation['success']) {
        $zip->close();
        throw new Exception($archiveValidation['message']);
    }
    $zip->close();
    updater_upload_stream('prepare', 'Update-Paket wird vorbereitet …', 48);


    // Move uploaded file to temporary location
    $temp_zip_path = WB_PATH . '/temp_upload.zip';
    $saved = $is_chunked_upload ? rename($uploaded_file, $temp_zip_path) : move_uploaded_file($uploaded_file, $temp_zip_path);
    if (!$saved) {
        throw new Exception($LANG['ERROR_SAVE_FAILED']);
    }

    // Calculate checksum for informational purposes
    $uploaded_checksum = calculateFileHash($temp_zip_path);
    updater_upload_stream('prepare', 'Paketstruktur wird analysiert …', 62);


    // Check if ZIP is already in correct format (files directly in root)
    $zip_test = new ZipArchive();
    $zip_test->open($temp_zip_path);

    $has_wbce_marker = false;
    $markers_found = [];

    // Check first 20 files to see structure (more thorough check)
    for ($i = 0; $i < min(20, $zip_test->numFiles); $i++) {
        $stat = $zip_test->statIndex($i);
        $path = $stat['name'];

        // Look for typical WBCE directories/files in root
        // index.php, admin/, framework/, modules/, templates/, include/, languages/
        if (preg_match('#^(index\.php|admin/|framework/|modules/|templates/|include/|languages/|media/)#', $path)) {
            $has_wbce_marker = true;
            $markers_found[] = $path;

            // Need at least 2 markers to be sure it's correct format
            if (count($markers_found) >= 2) {
                break;
            }
        }
    }
    $zip_test->close();

    // Always derive the authoritative target version from the package itself.
    // A stale form value or previous transaction must never describe a
    // different release than the ZIP which is actually going to be installed.
    $target_version = '';
    {
        $zip_ver = new ZipArchive();
        if ($zip_ver->open($temp_zip_path) === true) {
            $changelog_content = false;
            for ($i = 0; $i < $zip_ver->numFiles; $i++) {
                $stat = $zip_ver->statIndex($i);
                $entry_name = $stat['name'];
                if (preg_match('#(?:^|/)admin/interface/version\.php$#i', $entry_name)) {
                    $version_content = $zip_ver->getFromIndex($i);
                    if ($version_content !== false && preg_match("/define\\s*\\(\\s*['\"]NEW_WBCE_VERSION['\"]\\s*,\\s*['\"]([^'\"]+)['\"]/i", $version_content, $version_match)) {
                        $candidate = trim($version_match[1]);
                        if (preg_match('/^v?\d+\.\d+(?:\.\d+)?(?:[-+][0-9A-Za-z][0-9A-Za-z.-]*)?$/', $candidate)) {
                            $target_version = $candidate;
                            break;
                        }
                    }
                }
                $depth = substr_count(rtrim($entry_name, '/'), '/');
                if ($depth <= 1 && basename($entry_name) === 'CHANGELOG.md') {
                    $changelog_content = $zip_ver->getFromIndex($i);
                }
            }
            $zip_ver->close();

            if (empty($target_version) && $changelog_content !== false &&
                preg_match('/^#{1,3}\s+\[?(\d+\.\d+\.\d+(?:[-+][0-9A-Za-z][0-9A-Za-z.-]*)?)/m', $changelog_content, $ver_match)) {
                $target_version = $ver_match[1];
            }
        }
    }
    if ($target_version === '') {
        throw new Exception($LANG['ERROR_VERSION_NOT_FOUND'] ?? 'Die Zielversion konnte im Update-Paket nicht eindeutig ermittelt werden.');
    }
    updater_update_log_start($target_version, 'Manueller Upload');
    updater_update_log($target_version, 'Information', 'Manueller Upload', 'Update-Paket wird geprüft und vorbereitet.');
    updater_upload_stream('prepare', 'Update-Dateien werden bereitgestellt …', 78);

    $final_zip_path = WB_PATH . '/wbceup.zip';

    if ($has_wbce_marker) {
        // ZIP is already in correct format - just copy it

        if (!copy($temp_zip_path, $final_zip_path)) {
            @unlink($temp_zip_path);
            throw new Exception($LANG['ERROR_SAVE_FAILED']);
        }

        @unlink($temp_zip_path);

    } else {
        // ZIP needs repacking (probably GitHub format)

        require_once __DIR__ . '/repack_helper.php';
        $repack_result = repackZip($temp_zip_path, $final_zip_path, null, 'wbce', $LANG);


        // Delete temporary file
        @unlink($temp_zip_path);

        if (!$repack_result['success']) {
            throw new Exception($LANG['ERROR_REPACK_FAILED'] . ': ' . $repack_result['message']);
        }

    }

    updater_update_log($target_version, 'Information', 'Manueller Upload', 'Update-Paket wurde erfolgreich vorbereitet.');
    updater_upload_stream('prepare', 'Update-Paket ist vorbereitet.', 92);


} catch (Throwable $e) {
    $errors[] = $e->getMessage();
    $success = false;
    updater_update_log($target_version, 'Error', 'Manueller Upload', $e->getMessage());
    // Cleanup
    @unlink(WB_PATH . '/temp_upload.zip');
    @unlink(WB_PATH . '/wbceup.zip');
}

// Step 2: Prepare update execution (using integrated script)
// NOTE: No longer downloading external wbce_update_unzip.php
// Using integrated execute_update.php from this module instead

// Step 3: Enable maintenance mode (optional)
$maintenance_activated    = false;
$maintenance_already_active = false;

if ($success && $enable_maintenance) {
    require_once __DIR__ . '/maintenance_helper.php';
    $maint = updater_enable_maintenance($errors, $LANG);
    $maintenance_activated      = $maint['activated'];
    $maintenance_already_active = $maint['already_active'];
}

// Generate output
$update_token = $success ? updater_create_transaction(WB_PATH . '/wbceup.zip', $target_version, 'upload') : '';
if ($success && $update_token === '') {
    $success = false;
    $errors[] = $LANG['ERROR_TRANSACTION_TOKEN'];
}
$update_url = WB_URL . '/modules/updater/execute_update.php';

if ($streaming) {
    if ($success) {
        updater_upload_stream('ready', 'Update-Paket bereit. Starte Aktualisierung …', 100, array(
            'ok' => true,
            'update_token' => $update_token,
            'maintenance_enabled' => (bool)$maintenance_activated,
            'version' => $target_version,
            'items' => array(
                'wbceup.zip – ' . $LANG['SUCCESS_UPDATE_PACKAGE'],
                'execute_update.php – ' . $LANG['SUCCESS_UPDATE_SCRIPT'],
            ),
        ));
    } else {
        updater_upload_stream('error', (string)($errors[0] ?? $LANG['ERROR_UPLOAD_FAILED']), 0, array('ok' => false));
    }
    exit;
}

// The main Updater runs preparation in the existing overlay.  Keep the
// browser on its current admin page and return only the data required to
// start the protected execution request.
if ((string)($_POST['ajax'] ?? '') === '1' && strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest') {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    http_response_code($success ? 200 : 422);
    echo json_encode(array(
        'ok' => $success,
        'message' => $success ? '' : (string)($errors[0] ?? $LANG['ERROR_UPLOAD_FAILED']),
        'update_token' => $update_token,
        'maintenance_enabled' => (bool)$maintenance_activated,
        'version' => $target_version,
    ), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    exit;
}

?>
<!DOCTYPE html>
<html lang="<?php echo LANGUAGE; ?>">
<head>
    <meta charset="utf-8">
    <title><?php echo $LANG['TOOL_NAME']; ?></title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            max-width: 800px;
            margin: 40px auto;
            padding: 20px;
            background: #f5f5f5;
        }
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
        .warning {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
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
        .button-secondary {
            background: #6c757d;
        }
        .button-secondary:hover {
            background: #545b62;
        }
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
        }
        .file-list {
            margin: 15px 0;
            padding-left: 20px;
        }
        .file-list li {
            margin: 5px 0;
        }
        .loading-overlay {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 9999;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            background: rgba(0,0,0,0.55);
        }
        .loading-overlay.active { display: flex; }
        .spinner {
            width: 52px;
            height: 52px;
            border: 5px solid rgba(255,255,255,0.3);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin .8s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        .loading-text { margin-top: 18px; color: #fff; font-size: 17px; font-weight: bold; }
        .loading-subtext { margin-top: 8px; color: rgba(255,255,255,0.78); font-size: 13px; }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($success): ?>
            <h2 class="success">✓ <?php echo $LANG['UPLOAD_SUCCESS_TITLE']; ?></h2>

            <div class="info-box">
                <strong><?php echo $LANG['UPLOAD_FILES_PREPARED']; ?></strong>
                <ul class="file-list">
                    <li><code>wbceup.zip</code> - <?php echo $LANG['SUCCESS_UPDATE_PACKAGE']; ?></li>
                    <li><code>execute_update.php</code> - <?php echo $LANG['SUCCESS_UPDATE_SCRIPT']; ?> (integriert)</li>
                </ul>
            </div>

            <?php if ($uploaded_checksum): ?>
            <div class="info-box checksum-info-box">
                <strong><?php echo htmlspecialchars($LANG['CHECKSUM_INFO']); ?>:</strong>
                <code class="checksum-box"><?php echo htmlspecialchars($uploaded_checksum, ENT_QUOTES, 'UTF-8'); ?></code>
                <p><?php echo htmlspecialchars($LANG['CHECKSUM_VERIFY_INFO']); ?></p>
            </div>
            <?php endif; ?>

            <?php if ($maintenance_activated && $maintenance_already_active): ?>
                <div class="info-box">
                    <strong>✓ <?php echo $LANG['MAINTENANCE_ALREADY_ACTIVE']; ?></strong>
                    <p><?php echo $LANG['MAINTENANCE_ALREADY_ACTIVE_INFO']; ?></p>
                    <p><strong><?php echo $LANG['MAINTENANCE_DISABLE_INFO']; ?></strong></p>
                </div>
            <?php elseif ($maintenance_activated): ?>
                <div class="warning">
                    <strong>✓ <?php echo $LANG['MAINTENANCE_ENABLED']; ?></strong>
                    <p><?php echo $LANG['MAINTENANCE_INFO']; ?></p>
                    <p><strong><?php echo $LANG['MAINTENANCE_DISABLE_INFO']; ?></strong></p>
                </div>
            <?php elseif ($enable_maintenance && !$maintenance_activated): ?>
                <div class="warning">
                    <strong>⚠ <?php echo $LANG['MAINTENANCE_NOT_ACTIVATED']; ?></strong>
                    <p><?php echo $LANG['MAINTENANCE_MANUAL_INFO']; ?></p>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="warning">
                    <strong><?php echo $LANG['WARNINGS_OCCURRED']; ?></strong>
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <p><strong><?php echo $LANG['READY_TO_UPDATE']; ?></strong></p>
            <p><?php echo $LANG['CLICK_BUTTON_TO_START']; ?></p>

            <form method="post" action="<?php echo $update_url; ?>" style="display:inline">
                <input type="hidden" name="update_token" value="<?php echo htmlspecialchars($update_token); ?>">
                <input type="hidden" name="version" value="<?php echo htmlspecialchars($target_version); ?>">
                <button type="submit" class="button" id="start-update-btn">🚀 <?php echo $LANG['START_UPDATE_NOW']; ?></button>
            </form>

            <p style="margin-top: 30px; font-size: 14px; color: #666;">
                <?php echo $LANG['OR_MANUAL']; ?>: <?php echo $LANG['START_UPDATE_NOW']; ?>
            </p>

            <p style="margin-top: 20px;">
                <a href="<?php echo ADMIN_URL; ?>/admintools/tool.php?tool=updater" class="button button-secondary">
                    ← <?php echo $LANG['BACK_TO_UPDATER']; ?>
                </a>
            </p>

        <?php else: ?>
            <h2 class="error">✗ <?php echo $LANG['ERROR_TITLE']; ?></h2>

            <div class="error">
                <strong><?php echo $LANG['ERROR_OCCURRED']; ?></strong>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <p>
                <a href="<?php echo ADMIN_URL; ?>/admintools/tool.php?tool=updater" class="button button-secondary">
                    ← <?php echo $LANG['BACK_TO_UPDATER']; ?>
                </a>
            </p>
        <?php endif; ?>
    </div>
    <div id="loading-overlay" class="loading-overlay" aria-live="assertive" aria-busy="true">
        <div class="spinner" aria-hidden="true"></div>
        <div class="loading-text">⏳ <?php echo $LANG['UPDATE_RUNNING']; ?>...</div>
        <div class="loading-subtext"><?php echo $LANG['UPDATE_RUNNING_HINT']; ?></div>
    </div>
    <script>
    document.querySelector('form[action="<?php echo htmlspecialchars($update_url, ENT_QUOTES, 'UTF-8'); ?>"]')?.addEventListener('submit', function(event) {
        if (event.defaultPrevented) return;
        document.getElementById('loading-overlay').classList.add('active');
        const button = document.getElementById('start-update-btn');
        if (button) button.disabled = true;
    });
    </script>
</body>
</html>
