<?php
require_once dirname(__DIR__, 2).'/config.php';

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$MF = array();
require __DIR__.'/languages/EN.php';
$language = defined('LANGUAGE') ? preg_replace('/[^A-Z]/', '', strtoupper((string) LANGUAGE)) : 'EN';
if ($language !== 'EN' && is_file(__DIR__.'/languages/'.$language.'.php')) require __DIR__.'/languages/'.$language.'.php';

$respond = static function ($ok, $message, array $extra = array()) {
    if (!$ok) http_response_code(400);
    echo json_encode(array_merge(array('ok' => (bool) $ok, 'message' => (string) $message), $extra), JSON_UNESCAPED_UNICODE);
    exit;
};
$scalar = static function ($key, $default = '') {
    if (!isset($_POST[$key]) || (!is_scalar($_POST[$key]) && $_POST[$key] !== null)) return $default;
    return (string) $_POST[$key];
};

$sectionId = (int) $scalar('section_id', '0');
$token = $scalar('token');
$uploadId = $scalar('upload_id');
$field = $scalar('field');
$name = basename(str_replace('\\', '/', $scalar('name')));
$index = (int) $scalar('index', '-1');
$chunks = (int) $scalar('chunks', '0');
$total = (int) $scalar('total', '0');
$tokenData = $_SESSION['miniform_chunk_tokens'][$sectionId] ?? null;

if (!is_array($tokenData) || time() - (int) ($tokenData['created'] ?? 0) > 7200
    || !hash_equals((string) ($tokenData['token'] ?? ''), $token)) $respond(false, $MF['SECURITY_FAILED']);
$limitQuery = $database->query("SELECT `upload_limit_mb` FROM `".TABLE_PREFIX."mod_miniform` WHERE `section_id`=".$sectionId);
$limitRow = $limitQuery && $limitQuery->numRows() ? $limitQuery->fetchRow() : array();
$limitMb = max(1, min(512, (int) ($limitRow['upload_limit_mb'] ?? 512)));
if (!preg_match('/^[a-f0-9]{24,64}$/D', $uploadId) || !preg_match('/^[A-Za-z0-9_-]{1,128}$/D', $field)
    || $name === '' || strlen($name) > 255 || $index < 0 || $chunks < 1 || $index >= $chunks
    || $total < 1 || $total > $limitMb * 1024 * 1024) $respond(false, sprintf($MF['UPLOAD_TOO_LARGE'], $limitMb));

$allowed = array_filter(array_map('trim', explode(',', strtolower((string) ($tokenData['allowed'] ?? '')))), 'strlen');
$extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
$blocked = array('php','php3','php4','php5','php7','php8','phtml','pht','phar','cgi','pl','py','sh','bash','htaccess','user.ini');
if (!in_array($extension, $allowed, true) || in_array($extension, $blocked, true)) $respond(false, $MF['INVALID']);
if (!isset($_FILES['chunk']['tmp_name']) || !is_uploaded_file($_FILES['chunk']['tmp_name'])
    || (int) ($_FILES['chunk']['size'] ?? 0) > 1536 * 1024) $respond(false, $MF['UPLOAD_INVALID_REQUEST']);

$directory = WB_PATH.'/temp/miniform-chunks';
if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) $respond(false, $MF['UPLOAD_STORAGE_FAILED']);
foreach (glob($directory.'/*.part') ?: array() as $old) {
    if (is_file($old) && filemtime($old) < time() - 86400) @unlink($old);
}
$path = $directory.'/'.hash('sha256', session_id().':'.$uploadId).'.part';
$current = is_file($path) ? (int) filesize($path) : 0;
$existing = $_SESSION['miniform_chunk_uploads'][$uploadId] ?? null;
if (($index === 0 && $current !== 0) || ($index > 0 && (!is_array($existing) || (int) ($existing['next'] ?? -1) !== $index))) $respond(false, $MF['UPLOAD_SEQUENCE_ERROR']);
$input = fopen($_FILES['chunk']['tmp_name'], 'rb');
$output = fopen($path, $index === 0 ? 'wb' : 'ab');
if (!is_resource($input) || !is_resource($output)) $respond(false, $MF['UPLOAD_STORAGE_FAILED']);
$written = stream_copy_to_stream($input, $output);
fclose($input);
fclose($output);
if ($written === false || filesize($path) > $total) { @unlink($path); $respond(false, $MF['UPLOAD_STORAGE_FAILED']); }

$_SESSION['miniform_chunk_uploads'][$uploadId] = array(
    'section_id' => $sectionId, 'field' => $field, 'name' => $name,
    'path' => $path, 'size' => $total, 'next' => $index + 1, 'ready' => false,
);
if ($index + 1 === $chunks) {
    if ((int) filesize($path) !== $total) { @unlink($path); unset($_SESSION['miniform_chunk_uploads'][$uploadId]); $respond(false, $MF['UPLOAD_INCOMPLETE']); }
    $_SESSION['miniform_chunk_uploads'][$uploadId]['ready'] = true;
}
$respond(true, $MF['UPLOAD_PROGRESS'], array('upload_id' => $uploadId, 'ready' => $index + 1 === $chunks));
