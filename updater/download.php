<?php
/**
 * Updater - Download Handler
 *
 * Lädt Update-Paket herunter und bereitet Update vor
 *
 * @category    module
 * @package     updater
 * @version     1.0.2
 * @author      WBCE Community
 * @copyright   2026 WBCE Community
 * @license     MIT License
 */

// Include WBCE framework
require '../../config.php';
require_once WB_PATH . '/framework/Admin.php';

// Load central configuration
require_once __DIR__ . '/config_defaults.php';

// Include checksum validator
require_once __DIR__ . '/checksum_validator.php';
require_once __DIR__ . '/update_transaction.php';
require_once __DIR__ . '/UpdateLog.php';

function updater_download_resolve_store_offer($version, $checksum, $downloadUrl)
{
    global $database;
    if (!isset($database) || !is_object($database) || !method_exists($database, 'query')) return null;
    $expectedHash=strtolower(trim((string)$checksum));
    if (!preg_match('/^[a-f0-9]{64}$/',$expectedHash) || !filter_var($downloadUrl,FILTER_VALIDATE_URL)) return null;
    $clientFile=WB_PATH.'/modules/store/HttpClient.php';
    if (is_file($clientFile)) require_once $clientFile;
    $sources=$database->query("SELECT `catalog_url`,`access_token` FROM `{TP}mod_store_sources` WHERE `active`=1");
    while($sources&&($source=$sources->fetchRow(MYSQLI_ASSOC))){
        try {
            $catalogUrl=(string)$source['catalog_url'];$token=(string)$source['access_token'];
            if(class_exists('WbceRepositoryHttpClient')){$client=new WbceRepositoryHttpClient();$catalog=$client->json($catalogUrl,$token);}else{$catalog=null;}
            if(!is_array($catalog)||!is_array($catalog['packages']??null))continue;
            $catalogHost=strtolower((string)parse_url($catalogUrl,PHP_URL_HOST));
            foreach($catalog['packages'] as $package){
                if(!is_array($package)||($package['type']??'')!=='cms'||($package['slug']??'')!=='wbce-cms')continue;
                $url=(string)($package['download_url']??'');
                if((string)($package['version']??'')!==(string)$version||!hash_equals($expectedHash,strtolower((string)($package['sha256']??'')))||!hash_equals((string)$downloadUrl,$url)||parse_url($url,PHP_URL_SCHEME)!=='https'||strtolower((string)parse_url($url,PHP_URL_HOST))!==$catalogHost)continue;
                $current=defined('WBCE_VERSION')?(string)WBCE_VERSION:'';$delta=null;foreach((array)($package['deltas']??array()) as $candidate){$deltaUrl=is_array($candidate)?updater_delta_download_url($candidate,$catalogUrl):'';if(!is_array($candidate)||!updater_delta_is_preferred($current,(string)($candidate['from_version']??($candidate['source_version']??'')),(string)($package['version']??''))||!preg_match('/^[a-f0-9]{64}$/i',(string)($candidate['sha256']??''))||(int)($candidate['size']??0)<1||!filter_var($deltaUrl,FILTER_VALIDATE_URL)||parse_url($deltaUrl,PHP_URL_SCHEME)!=='https'||strtolower((string)parse_url($deltaUrl,PHP_URL_HOST))!==$catalogHost)continue;$delta=array('url'=>$deltaUrl,'sha256'=>strtolower($candidate['sha256']),'size'=>(int)$candidate['size'],'from_version'=>$current);break;}
                return array('url'=>$url,'sha256'=>$expectedHash,'version'=>(string)$version,'size'=>(int)($package['size']??0),'token'=>$token,'delta'=>$delta,'expires'=>time()+300);
            }
        }catch(Throwable $ignored){}
    }
    return null;
}

// Load language file
$lang = (file_exists(__DIR__ . '/languages/' . LANGUAGE . '.php'))
    ? __DIR__ . '/languages/' . LANGUAGE . '.php'
    : __DIR__ . '/languages/EN.php';
require $lang;

// fetch() consumes Store preparations as NDJSON. Rejections must therefore
// keep that protocol instead of redirecting to a rendered admin page.
$streaming = (string)($_POST['stream'] ?? '') === '1'
    && strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
function updater_download_stream($state, $message, $percent = 0, array $data = array())
{
    global $streaming;
    if (!$streaming) return;
    echo json_encode(array_merge(array('state'=>$state, 'message'=>$message, 'percent'=>(int)$percent), $data), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . "\n";
    @ob_flush(); @flush();
}
function updater_download_reject($message, $status = 400)
{
    global $streaming;
    if ($streaming) {
        http_response_code($status);
        header('Content-Type: application/x-ndjson; charset=utf-8');
        header('Cache-Control: no-store');
        updater_download_stream('error', (string)$message, 0, array('ok'=>false));
        exit;
    }
    header('Location: ' . ADMIN_URL . '/admintools/tool.php?tool=updater');
    exit;
}

// Security check: Admin only
$admin = new admin('Admintools', 'admintools', false, false);

if (!empty($updater_disabled)) {
    updater_download_reject($LANG['TOOL_DISABLED'] ?? 'Der Updater ist deaktiviert.', 403);
}

// FTAN Check
if (!$admin->checkFTAN()) {
    updater_download_reject($LANG['ERROR_UPDATE_AUTH_INVALID'] ?? 'Die Update-Autorisierung ist ungültig oder abgelaufen. Bitte die Seite neu laden.', 403);
}

// Check if POST data is present
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    updater_download_reject($LANG['ERROR_METHOD_NOT_ALLOWED'] ?? 'Diese Aktion ist nur über ein gesichertes Formular möglich.', 405);
}

// Get POST parameters
$store_offer = isset($_POST['store_offer']) && is_scalar($_POST['store_offer']) ? (string)$_POST['store_offer'] : '';
$download_url = isset($_POST['download_url']) && is_scalar($_POST['download_url']) ? trim((string)$_POST['download_url']) : '';
$target_version = isset($_POST['target_version']) && is_scalar($_POST['target_version']) ? trim((string)$_POST['target_version']) : '';
$checksum = isset($_POST['checksum']) && is_scalar($_POST['checksum']) ? trim((string)$_POST['checksum']) : '';
$backup_confirmed = isset($_POST['backup_confirmed']) && $_POST['backup_confirmed'] === '1';
$enable_maintenance = isset($_POST['enable_maintenance']) && $_POST['enable_maintenance'] === '1';
$force_full = isset($_POST['force_full']) && $_POST['force_full'] === '1';

// A Store offer is whitelisted in the authenticated session by store_updates.php.
$storeOffers=updater_transaction_session_get('WBCE_UPDATER_STORE_OFFERS',array());
if(!is_array($storeOffers))$storeOffers=array();
$storeOffer = $store_offer !== '' ? ($storeOffers[$store_offer] ?? null) : null;
// An offer identifier is only a server-side lookup key. Bind it again to the
// concrete card selected in the browser before it can replace request values.
if (is_array($storeOffer) && (
    ($target_version !== '' && !hash_equals((string)$storeOffer['version'], $target_version))
    || ($checksum !== '' && !hash_equals(strtolower((string)$storeOffer['sha256']), strtolower($checksum)))
    || ($download_url !== '' && !hash_equals((string)$storeOffer['url'], $download_url))
)) {
    $storeOffer = null;
}
if(!is_array($storeOffer))$storeOffer=updater_download_resolve_store_offer($target_version,$checksum,$download_url);
$deltaMeta=array();$updateType='full';if (is_array($storeOffer) && !empty($storeOffer['expires']) && (int)$storeOffer['expires'] >= time()) { $download_url=(string)$storeOffer['url']; $target_version=(string)$storeOffer['version']; $checksum=(string)$storeOffer['sha256'];if(!$force_full&&is_array($storeOffer['delta']??null)){$deltaMeta=$storeOffer['delta'];$download_url=(string)$deltaMeta['url'];$updateType='diff';} }
// Validate inputs
if (empty($download_url)) {
    updater_download_reject($LANG['ERROR_RELEASE_API_UNAVAILABLE'] ?? 'Das ausgewählte Store-Update ist nicht mehr verfügbar. Bitte die Liste neu laden.', 409);
}

// Security: Validate download URL to prevent SSRF attacks
$parsed_url = parse_url($download_url);
if (!$parsed_url) {
    updater_download_reject('Die Update-Adresse ist ungültig.', 400);
}

// Only allow HTTPS protocol
if (!isset($parsed_url['scheme']) || $parsed_url['scheme'] !== 'https') {
    updater_download_reject('Die Update-Adresse muss HTTPS verwenden.', 400);
}

// Custom source: allow only if URL matches the exact configured value (server-side whitelist)
$is_store_source = is_array($storeOffer) && !empty($storeOffer['expires']) && (int)$storeOffer['expires'] >= time();
$is_custom_source = !empty($updater_custom_source_url)
    && hash_equals($updater_custom_source_url, $download_url);

// Standard path: only allow GitHub domains
if (!$is_custom_source && !$is_store_source) {
    if (!isset($parsed_url['host']) ||
        !preg_match('/^(api\.)?github\.com$/i', $parsed_url['host'])) {
        updater_download_reject('Die Update-Quelle ist nicht freigegeben.', 403);
    }
}

// Security: Validate version format
if (!empty($target_version) && !preg_match('/^v?\d+\.\d+(?:\.\d+)?(?:[-+][0-9A-Za-z][0-9A-Za-z.-]*)?$/', $target_version)) {
    updater_download_reject('Die Zielversion ist ungültig.', 400);
}

if (!$backup_confirmed) {
    updater_download_reject($LANG['ERROR_BACKUP_NOT_CONFIRMED'] ?? 'Bitte zuerst das Backup bestätigen.', 409);
}

// Check write permissions
if (!is_writable(WB_PATH)) {
    updater_download_reject($LANG['ERROR_SAVE_FAILED'] ?? 'Das CMS-Verzeichnis ist nicht beschreibbar.', 500);
}

if ($streaming) {
    while (ob_get_level() > 0) @ob_end_flush();
    @ini_set('output_buffering', '0');
    header('Content-Type: application/x-ndjson; charset=utf-8');
    header('Cache-Control: no-store');
    header('X-Accel-Buffering: no');
    updater_download_stream('download', $updateType === 'diff' ? ($LANG['LOADING_DIFF_DOWNLOAD'] ?? 'Diff-Update wird heruntergeladen') : ($LANG['LOADING_FULL_DOWNLOAD'] ?? $LANG['LOADING_DOWNLOAD']), 3);
}

// Each target release has its own short, current update log.
updater_update_log_start($target_version, 'Store');
updater_update_log($target_version, 'Information', 'Download', $updateType === 'diff' ? 'Diff-Update wird heruntergeladen.' : 'Vollupdate wird heruntergeladen.');

// Start process
$errors = [];
$success = true;

// Step 1: Download and repack update package
try {
    $temp_zip_path = WB_PATH . '/temp_download.zip';
    $target = @fopen($temp_zip_path, 'wb');
    if (!is_resource($target)) {
        throw new Exception($LANG['ERROR_DOWNLOAD_EMPTY']);
    }
    $bytes_written = 0;
    $signature = '';
    $downloadError = '';

    // Use the proven Store HTTP client for Store artifacts. It supplies the
    // required Bearer token and has the same response checks as Store installs.
    $expectedDownloadSize = $deltaMeta ? (int)($deltaMeta['size'] ?? 0) : (int)($storeOffer['size'] ?? 0);
    if ($is_store_source && class_exists('WbceRepositoryHttpClient') && $expectedDownloadSize > 0) {
        fclose($target); $target = null; @unlink($temp_zip_path);
        $client = new WbceRepositoryHttpClient();
        $downloaded = $client->download($download_url, $expectedDownloadSize, (string)($storeOffer['token'] ?? ''));
        if (!is_string($downloaded) || !is_file($downloaded) || !@rename($downloaded, $temp_zip_path)) {
            if (is_string($downloaded) && is_file($downloaded)) @unlink($downloaded);
            throw new Exception($LANG['ERROR_SAVE_FAILED']);
        }
        $bytes_written = (int)@filesize($temp_zip_path);
        $signature = (string)@file_get_contents($temp_zip_path, false, null, 0, 4);
    } elseif (function_exists('curl_init')) {
        $curl = curl_init($download_url);
        if ($curl === false) {
            fclose($target);
            @unlink($temp_zip_path);
            throw new Exception($LANG['ERROR_DOWNLOAD_EMPTY']);
        }
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_CONNECTTIMEOUT => min(15, WBCE_UPDATER_HTTP_TIMEOUT),
            CURLOPT_TIMEOUT => WBCE_UPDATER_HTTP_TIMEOUT * 4,
            CURLOPT_FOLLOWLOCATION => !($is_custom_source || $is_store_source),
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
            CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTPS,
            CURLOPT_USERAGENT => 'WBCE-Updater/1.0.28',
            CURLOPT_HTTPHEADER => $is_store_source && !empty($storeOffer['token']) ? array('Authorization: Bearer '.(string)$storeOffer['token']) : array(),
            CURLOPT_WRITEFUNCTION => static function ($handle, $chunk) use ($target, &$bytes_written, &$signature, &$downloadError, $LANG) {
                $length = strlen($chunk);
                if ($signature === '' && $length > 0) {
                    $signature = substr($chunk, 0, 4);
                }
                if ($bytes_written + $length > WBCE_UPDATER_MAX_UPLOAD_SIZE) {
                    $downloadError = sprintf($LANG['ERROR_FILE_TOO_LARGE_MB'], WBCE_UPDATER_MAX_UPLOAD_SIZE / 1048576);
                    return 0;
                }
                $offset = 0;
                while ($offset < $length) {
                    $written = fwrite($target, substr($chunk, $offset));
                    if (!is_int($written) || $written <= 0) {
                        $downloadError = $LANG['ERROR_SAVE_FAILED'];
                        return 0;
                    }
                    $offset += $written;
                }
                $bytes_written += $length;
                return $length;
            },
        ));
        $curlResult = curl_exec($curl);
        $statusCode = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $effectiveUrl = (string)curl_getinfo($curl, CURLINFO_EFFECTIVE_URL);
        $curlError = curl_error($curl);
        if (PHP_VERSION_ID < 80500) curl_close($curl);

        $effectiveParts = parse_url($effectiveUrl);
        $effectiveHost = is_array($effectiveParts) && isset($effectiveParts['host']) ? strtolower((string)$effectiveParts['host']) : '';
        $githubHostValid = (bool)preg_match('/^(?:api\.)?github\.com$|^(?:objects|github-releases)\.githubusercontent\.com$/i', $effectiveHost);
        $customTargetValid = ($is_custom_source || $is_store_source) && hash_equals($download_url, $effectiveUrl);
        if ($curlResult !== true || $statusCode < 200 || $statusCode >= 300 || (!$githubHostValid && !$customTargetValid)) {
            if ($downloadError === '') {
                $downloadError = $curlError !== '' ? $curlError : $LANG['ERROR_DOWNLOAD_EMPTY'];
            }
        }
    } else {
        $context = stream_context_create(array(
            'http' => array(
                'method' => 'GET',
                'header' => "User-Agent: WBCE-Updater/1.0.28\r\n",
                'timeout' => WBCE_UPDATER_HTTP_TIMEOUT * 2,
                'follow_location' => $is_custom_source ? 0 : 1,
                'max_redirects' => 5,
                'ignore_errors' => false,
            ),
            'ssl' => array('verify_peer' => true, 'verify_peer_name' => true),
        ));
        $source = @fopen($download_url, 'rb', false, $context);
        if (!is_resource($source)) {
            $downloadError = $LANG['ERROR_DOWNLOAD_EMPTY'];
        } else {
            while (!feof($source)) {
                $chunk = fread($source, 1048576);
                if ($chunk === false) {
                    $downloadError = $LANG['ERROR_DOWNLOAD_EMPTY'];
                    break;
                }
                if ($chunk === '') continue;
                if ($signature === '') $signature = substr($chunk, 0, 4);
                $length = strlen($chunk);
                $bytes_written += $length;
                if ($bytes_written > WBCE_UPDATER_MAX_UPLOAD_SIZE || fwrite($target, $chunk) !== $length) {
                    $downloadError = $bytes_written > WBCE_UPDATER_MAX_UPLOAD_SIZE
                        ? sprintf($LANG['ERROR_FILE_TOO_LARGE_MB'], WBCE_UPDATER_MAX_UPLOAD_SIZE / 1048576)
                        : $LANG['ERROR_SAVE_FAILED'];
                    break;
                }
            }
            fclose($source);
        }
    }

    if ($downloadError !== '') {
        fclose($target);
        @unlink($temp_zip_path);
        throw new Exception($downloadError);
    }
    if (is_resource($target)) {
        if (!fflush($target)) { fclose($target); @unlink($temp_zip_path); throw new Exception($LANG['ERROR_SAVE_FAILED']); }
        fclose($target);
    }
    if ($bytes_written < 4 || substr($signature, 0, 2) !== 'PK') {
        @unlink($temp_zip_path);
        throw new Exception($LANG['ERROR_DOWNLOAD_NOT_ZIP']);
    }

    updater_download_stream('validate', $LANG['SUCCESS_FILES_DOWNLOADED'], 64);
    // Remote packages always fail closed; manual uploads use upload.php.
    $expectedHash = extractDigestHash($deltaMeta?(string)$deltaMeta['sha256']:$checksum);
    if (!$expectedHash) {
        @unlink($temp_zip_path);
        throw new Exception($LANG['WARNING_NO_CHECKSUM']);
    }
    if (!validateFileChecksum($temp_zip_path, $expectedHash)) {
        @unlink($temp_zip_path);
        throw new Exception($LANG['ERROR_CHECKSUM_MISMATCH']);
    }
    updater_download_stream('validate', $LANG['CHECKSUM_VALIDATED'], 74);
    updater_update_log($target_version, 'Information', 'Download', 'Prüfsumme des Update-Pakets bestätigt.');

    // ZIP umpacken (nur wbce/ Ordner extrahieren)
    require_once __DIR__ . '/repack_helper.php';

    $final_zip_path = WB_PATH . '/wbceup.zip';
    updater_download_stream('prepare', $LANG['UPDATE_RUNNING'], 82);
    if($deltaMeta){$in=new ZipArchive();$out=new ZipArchive();$ok=$in->open($temp_zip_path)===true&&$out->open($final_zip_path,ZipArchive::CREATE|ZipArchive::OVERWRITE)===true;$manifest=$ok?json_decode((string)$in->getFromName('manifest.json'),true):null;if(!$ok||!is_array($manifest)||($manifest['type']??'')!=='wbce-store-delta'||!hash_equals((string)$checksum,(string)($manifest['target']['sha256']??''))||!isset($manifest['changed']['install/update.php'])){if($ok){$in->close();$out->close();}@unlink($final_zip_path);$repack_result=array('success'=>false,'message'=>'Das Diff-Update enthält kein WBCE Update-Script. Bitte die Vollversion laden.');}else{foreach((array)($manifest['changed']??array()) as $path=>$entry){$data=$in->getFromName('files/'.$path);if(!is_string($data)||!preg_match('#^[A-Za-z0-9._ @()/-]+$#',$path)||str_contains($path,'..')){$ok=false;break;}$out->addFromString('wbce/'.$path,$data);}$out->addFromString('wbce/.wbce-delta-deletions.json',json_encode((array)($manifest['deleted']??array())));$in->close();$out->close();$repack_result=array('success'=>$ok,'message'=>'');}}
    else $repack_result = repackZip($temp_zip_path, $final_zip_path, null, 'wbce', $LANG);

    // Temporäre Datei löschen
    @unlink($temp_zip_path);

    if (!$repack_result['success']) {
        throw new Exception($LANG['ERROR_REPACK_FAILED'] . ': ' . $repack_result['message']);
    }
    updater_update_log($target_version, 'Information', 'Download', 'Update-Paket wurde erfolgreich vorbereitet.');

} catch (Throwable $e) {
    $errors[] = $e->getMessage();
    $success = false;
    updater_update_log($target_version, 'Error', 'Download', $e->getMessage());
    // Cleanup
    @unlink(WB_PATH . '/temp_download.zip');
}

// Step 2: Prepare update execution (using integrated script)
// NOTE: No longer downloading external wbce_update_unzip.php
// Using integrated execute_update.php from this module instead

// Step 3: Enable maintenance mode (optional)
$maintenance_activated      = false;
$maintenance_already_active = false;

if ($success && $enable_maintenance) {
    require_once __DIR__ . '/maintenance_helper.php';
    $maint = updater_enable_maintenance($errors, $LANG);
    $maintenance_activated      = $maint['activated'];
    $maintenance_already_active = $maint['already_active'];
}

// Generate output
$update_token = $success ? updater_create_transaction(WB_PATH . '/wbceup.zip', $target_version, 'download') : '';
if ($success && $update_token === '') {
    $success = false;
    $errors[] = $LANG['ERROR_TRANSACTION_TOKEN'];
}
$update_url = WB_URL . '/modules/updater/execute_update.php';

if ($streaming) {
    if ($success) {
        updater_download_stream('ready', $LANG['READY_TO_UPDATE'], 100, array(
            'ok'=>true,
            'update_token'=>$update_token,
            'maintenance_enabled'=>(bool)$maintenance_activated,
            'version'=>$target_version,
            'items'=>array('wbceup.zip – ' . $LANG['SUCCESS_UPDATE_PACKAGE']),
        ));
    } else {
        updater_download_stream('error', implode(' ', $errors) ?: $LANG['ERROR_DOWNLOAD_EMPTY'], 100, array('ok'=>false));
    }
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
            background: rgba(0,0,0,0.55);
            z-index: 9999;
            align-items: center;
            justify-content: center;
            flex-direction: column;
        }
        .loading-overlay.active {
            display: flex;
        }
        .spinner {
            width: 52px;
            height: 52px;
            border: 5px solid rgba(255,255,255,0.3);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        .loading-overlay .loading-text {
            margin-top: 18px;
            color: #fff;
            font-size: 17px;
            font-weight: bold;
        }
        .loading-overlay .loading-subtext {
            margin-top: 8px;
            color: rgba(255,255,255,0.75);
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($success): ?>
            <h2 class="success">✓ <?php echo $LANG['SUCCESS_TITLE']; ?></h2>

            <div class="info-box">
                <strong><?php echo $LANG['SUCCESS_FILES_DOWNLOADED']; ?></strong>
                <ul class="file-list">
                    <li><code>wbceup.zip</code> - <?php echo $LANG['SUCCESS_UPDATE_PACKAGE']; ?></li>
                    <li><code>execute_update.php</code> - <?php echo $LANG['SUCCESS_UPDATE_SCRIPT']; ?> (integriert)</li>
                </ul>
            </div>

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
                <button type="submit" class="button" id="start-update-btn" onclick="startUpdate(this)">🚀 <?php echo $LANG['START_UPDATE_NOW']; ?></button>
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

    <!-- Loading Overlay -->
    <div id="loading-overlay" class="loading-overlay">
        <div class="spinner"></div>
        <div class="loading-text">⏳ <?php echo $LANG['UPDATE_RUNNING']; ?>...</div>
        <div class="loading-subtext"><?php echo $LANG['UPDATE_RUNNING_HINT']; ?></div>
    </div>

    <script>
    function startUpdate(link) {
        document.getElementById('loading-overlay').classList.add('active');
        link.style.pointerEvents = 'none';
        link.style.opacity = '0.6';
    }
    </script>
</body>
</html>
