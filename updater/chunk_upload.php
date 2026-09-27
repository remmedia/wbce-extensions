<?php
/** Chunk receiver for update packages larger than the PHP request limit. */

require_once dirname(__DIR__, 2) . '/config.php';
require_once WB_PATH . '/framework/Admin.php';
require_once __DIR__ . '/config_defaults.php';
require_once __DIR__ . '/update_transaction.php';

$language = defined('LANGUAGE') ? strtoupper((string) LANGUAGE) : 'EN';
$languageFile = __DIR__ . '/languages/' . $language . '.php';
if (!is_file($languageFile)) {
    $languageFile = __DIR__ . '/languages/EN.php';
}
$LANG = array();
require $languageFile;

function updater_chunk_text(string $key): string
{
    global $LANG;
    return (string) ($LANG[$key] ?? $key);
}

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');
$admin = new admin('Admintools', 'admintools', false, false);

function updater_chunk_reply(bool $ok, string $message = '', array $data = array(), int $status = 200): void
{
    http_response_code($status);
    $json = json_encode(array_merge(array('ok' => $ok, 'message' => $message), $data), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    echo $json === false ? '{"ok":false,"message":"JSON encoding failed."}' : $json;
    exit;
}

if (!$admin->is_authenticated() || !$admin->isAdmin()) {
    updater_chunk_reply(false, updater_chunk_text('CHUNK_SESSION_EXPIRED'), array(), 403);
}

if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    updater_chunk_reply(false, updater_chunk_text('CHUNK_POST_ONLY'), array(), 405);
}
$token = isset($_POST['token']) && is_scalar($_POST['token']) ? (string)$_POST['token'] : '';
$sessionToken = (string) updater_transaction_session_get('WBCE_UPDATER_CHUNK_TOKEN', '');
$tokenPath = WB_PATH . '/temp/.wbce-updater-chunk-token-' . hash('sha256', $token) . '.json';
$tokenRaw = $token !== '' ? @file_get_contents($tokenPath) : false;
$tokenData = is_string($tokenRaw) ? json_decode($tokenRaw, true) : null;
$tokenValid = is_array($tokenData) && (int)($tokenData['expires'] ?? 0) >= time();
if (($sessionToken === '' || !hash_equals($sessionToken, $token)) && !$tokenValid) {
    updater_chunk_reply(false, updater_chunk_text('CHUNK_UPLOAD_SESSION_EXPIRED'), array(), 403);
}

$baseDir = WB_PATH . '/temp/updater_chunks';
if (!is_dir($baseDir) && !mkdir($baseDir, 0750, true) && !is_dir($baseDir)) {
    updater_chunk_reply(false, updater_chunk_text('CHUNK_TEMP_DIR_FAILED'), array(), 500);
}
function updater_chunk_state_path($baseDir, $id) { return $baseDir . '/' . $id . '.json'; }
function updater_chunk_state_save($baseDir, $id, array $state) { $json=json_encode($state,JSON_UNESCAPED_SLASHES); if(!is_string($json)||@file_put_contents(updater_chunk_state_path($baseDir,$id),$json,LOCK_EX)===false)return false;@chmod(updater_chunk_state_path($baseDir,$id),0600);return true; }
function updater_chunk_state_load($baseDir, $id) { $raw=@file_get_contents(updater_chunk_state_path($baseDir,$id));$data=is_string($raw)?json_decode($raw,true):null;return is_array($data)?$data:null; }
foreach (glob($baseDir . '/*') ?: array() as $oldFile) {
    if (is_file($oldFile) && filemtime($oldFile) < time() - 21600) {
        @unlink($oldFile);
    }
}

$action = isset($_POST['action']) && is_scalar($_POST['action']) ? (string)$_POST['action'] : '';
if ($action === 'init') {
    $name = isset($_POST['name']) && is_scalar($_POST['name']) ? basename((string)$_POST['name']) : '';
    $size = filter_var($_POST['size'] ?? null, FILTER_VALIDATE_INT);
    $chunks = filter_var($_POST['chunks'] ?? null, FILTER_VALIDATE_INT);
    if (!preg_match('/\.zip$/i', $name) || $size === false || $size < 1 || $size > WBCE_UPDATER_MAX_UPLOAD_SIZE || $chunks === false || $chunks < 1) {
        updater_chunk_reply(false, updater_chunk_text('CHUNK_INVALID_PACKAGE'), array(), 422);
    }
    try { $id = bin2hex(random_bytes(20)); }
    catch (Throwable $exception) { updater_chunk_reply(false, updater_chunk_text('CHUNK_TOKEN_FAILED'), array(), 500); }
    $path = $baseDir . '/' . $id . '.part';
    if (file_put_contents($path, '') === false) {
        updater_chunk_reply(false, updater_chunk_text('CHUNK_TEMP_FILE_FAILED'), array(), 500);
    }
$uploads=updater_transaction_session_get('WBCE_UPDATER_CHUNK_UPLOADS',array());
    if(!is_array($uploads))$uploads=array();
    $uploads[$id] = array(
        'path' => $path, 'name' => $name, 'size' => $size,
        'chunks' => $chunks, 'next' => 0, 'created' => time(), 'complete' => false,
    );
    updater_transaction_session_set('WBCE_UPDATER_CHUNK_UPLOADS',$uploads);
    if(!updater_chunk_state_save($baseDir,$id,$uploads[$id]))updater_chunk_reply(false,updater_chunk_text('CHUNK_TEMP_FILE_FAILED'),array(),500);
    updater_chunk_reply(true, '', array('upload_id' => $id));
}

$id = isset($_POST['upload_id']) && is_scalar($_POST['upload_id']) ? (string)$_POST['upload_id'] : '';
$uploads=updater_transaction_session_get('WBCE_UPDATER_CHUNK_UPLOADS',array());
if(!is_array($uploads))$uploads=array();
if (preg_match('/^[a-f0-9]{40}$/', $id) && empty($uploads[$id])) { $stored=updater_chunk_state_load($baseDir,$id); if(is_array($stored))$uploads[$id]=$stored; }
if (!preg_match('/^[a-f0-9]{40}$/', $id) || empty($uploads[$id])) {
    updater_chunk_reply(false, updater_chunk_text('CHUNK_UNKNOWN_UPLOAD'), array(), 404);
}
$upload = $uploads[$id];

if ($action === 'chunk') {
    $index = filter_var($_POST['index'] ?? null, FILTER_VALIDATE_INT);
    if ($index === false || $index !== (int) $upload['next'] || !isset($_FILES['chunk']) || $_FILES['chunk']['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($_FILES['chunk']['tmp_name'])) {
        updater_chunk_reply(false, updater_chunk_text('CHUNK_SEQUENCE_ERROR'), array(), 409);
    }
    $currentSize = is_file($upload['path']) ? filesize($upload['path']) : 0;
    $chunkSize = (int) $_FILES['chunk']['size'];
    if ($chunkSize < 1 || $currentSize + $chunkSize > (int) $upload['size'] || $currentSize + $chunkSize > WBCE_UPDATER_MAX_UPLOAD_SIZE) {
        updater_chunk_reply(false, updater_chunk_text('CHUNK_TOO_LARGE'), array(), 413);
    }
    $source = fopen($_FILES['chunk']['tmp_name'], 'rb');
    $target = fopen($upload['path'], 'ab');
    $copied = ($source && $target) ? stream_copy_to_stream($source, $target) : false;
    if (is_resource($source)) fclose($source);
    if (is_resource($target)) fclose($target);
    if ($copied !== $chunkSize) {
        updater_chunk_reply(false, updater_chunk_text('CHUNK_WRITE_FAILED'), array(), 500);
    }
    $upload['next']++;
    $uploads[$id]=$upload;updater_transaction_session_set('WBCE_UPDATER_CHUNK_UPLOADS',$uploads);
    if(!updater_chunk_state_save($baseDir,$id,$upload))updater_chunk_reply(false,updater_chunk_text('CHUNK_TEMP_FILE_FAILED'),array(),500);
    updater_chunk_reply(true);
}

if ($action === 'complete') {
    clearstatcache(true, $upload['path']);
    if ((int) $upload['next'] !== (int) $upload['chunks'] || !is_file($upload['path']) || filesize($upload['path']) !== (int) $upload['size']) {
        updater_chunk_reply(false, updater_chunk_text('CHUNK_INCOMPLETE'), array(), 409);
    }
    $completePath = $baseDir . '/' . $id . '.zip';
    if (!rename($upload['path'], $completePath)) {
        updater_chunk_reply(false, updater_chunk_text('CHUNK_FINALIZE_FAILED'), array(), 500);
    }
    $upload['path'] = $completePath;
    $upload['complete'] = true;
    $uploads[$id]=$upload;updater_transaction_session_set('WBCE_UPDATER_CHUNK_UPLOADS',$uploads);
    if(!updater_chunk_state_save($baseDir,$id,$upload))updater_chunk_reply(false,updater_chunk_text('CHUNK_TEMP_FILE_FAILED'),array(),500);
    updater_chunk_reply(true);
}

updater_chunk_reply(false, updater_chunk_text('CHUNK_UNKNOWN_ACTION'), array(), 400);
