<?php
require_once '../../config.php';
require_once WB_PATH . '/framework/Admin.php';

$admin = new admin('Admintools', 'admintools', false, false);
$language = defined('LANGUAGE') ? strtoupper((string) LANGUAGE) : 'EN';
$languageFile = __DIR__ . '/languages/' . $language . '.php';
if (!is_file($languageFile)) $languageFile = __DIR__ . '/languages/EN.php';
$DR_TEXT = array();
require $languageFile;
require_once __DIR__ . '/functions.inc.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

function droplets_chunk_reply($ok, $message, array $extra = array(), $status = 200)
{
    http_response_code((int) $status);
    echo json_encode(array_merge(array('ok' => (bool) $ok, 'message' => (string) $message), $extra), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    exit;
}

if (!$admin->is_authenticated() || !$admin->get_permission('admintools')) droplets_chunk_reply(false, $DR_TEXT['CHUNK_SESSION_EXPIRED'], array(), 403);
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') droplets_chunk_reply(false, $DR_TEXT['CHUNK_POST_ONLY'], array(), 405);
$token = isset($_POST['token']) && is_scalar($_POST['token']) ? (string) $_POST['token'] : '';
$sessionToken = (string) ($_SESSION['droplets_chunk_token'] ?? '');
if ($sessionToken === '' || !hash_equals($sessionToken, $token)) droplets_chunk_reply(false, $DR_TEXT['CHUNK_SESSION_EXPIRED'], array(), 403);

$baseDir = WB_PATH . '/temp/droplets_chunks';
if (!is_dir($baseDir) && !mkdir($baseDir, 0750, true) && !is_dir($baseDir)) droplets_chunk_reply(false, $DR_TEXT['CHUNK_STORAGE_FAILED'], array(), 500);
foreach (glob($baseDir . '/*') ?: array() as $oldFile) {
    if (is_file($oldFile) && filemtime($oldFile) < time() - 21600) @unlink($oldFile);
}

$action = isset($_POST['action']) && is_scalar($_POST['action']) ? (string) $_POST['action'] : '';
if ($action === 'init') {
    $name = isset($_POST['name']) && is_scalar($_POST['name']) ? basename((string) $_POST['name']) : '';
    $size = filter_var($_POST['size'] ?? null, FILTER_VALIDATE_INT);
    $chunks = filter_var($_POST['chunks'] ?? null, FILTER_VALIDATE_INT);
    if (!preg_match('/\.zip$/i', $name) || $size === false || $size < 1 || $size > 536870912 || $chunks === false || $chunks < 1 || $chunks !== (int) ceil($size / 1048576)) {
        droplets_chunk_reply(false, $DR_TEXT['CHUNK_INVALID_FILE'], array(), 422);
    }
    try { $id = bin2hex(random_bytes(20)); }
    catch (Throwable $exception) { droplets_chunk_reply(false, $DR_TEXT['CHUNK_STORAGE_FAILED'], array(), 500); }
    $path = $baseDir . '/' . $id . '.part';
    if (file_put_contents($path, '') === false) droplets_chunk_reply(false, $DR_TEXT['CHUNK_STORAGE_FAILED'], array(), 500);
    $_SESSION['droplets_chunk_uploads'][$id] = array('path' => $path, 'name' => $name, 'size' => $size, 'chunks' => $chunks, 'next' => 0);
    droplets_chunk_reply(true, '', array('upload_id' => $id));
}

$id = isset($_POST['upload_id']) && is_scalar($_POST['upload_id']) ? (string) $_POST['upload_id'] : '';
if (!preg_match('/^[a-f0-9]{40}$/', $id) || empty($_SESSION['droplets_chunk_uploads'][$id])) droplets_chunk_reply(false, $DR_TEXT['CHUNK_UNKNOWN_UPLOAD'], array(), 404);
$upload =& $_SESSION['droplets_chunk_uploads'][$id];

if ($action === 'chunk') {
    $index = filter_var($_POST['index'] ?? null, FILTER_VALIDATE_INT);
    if ($index === false || $index !== (int) $upload['next'] || !isset($_FILES['chunk']) || $_FILES['chunk']['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($_FILES['chunk']['tmp_name'])) {
        droplets_chunk_reply(false, $DR_TEXT['CHUNK_SEQUENCE_ERROR'], array(), 409);
    }
    $currentSize = is_file($upload['path']) ? (int) filesize($upload['path']) : 0;
    $chunkSize = (int) $_FILES['chunk']['size'];
    if ($chunkSize < 1 || $chunkSize > 1048576 || $currentSize + $chunkSize > (int) $upload['size']) droplets_chunk_reply(false, $DR_TEXT['CHUNK_SEQUENCE_ERROR'], array(), 413);
    $source = fopen($_FILES['chunk']['tmp_name'], 'rb');
    $target = fopen($upload['path'], 'ab');
    $copied = ($source && $target) ? stream_copy_to_stream($source, $target) : false;
    if (is_resource($source)) fclose($source);
    if (is_resource($target)) fclose($target);
    if ($copied !== $chunkSize) droplets_chunk_reply(false, $DR_TEXT['CHUNK_STORAGE_FAILED'], array(), 500);
    $upload['next']++;
    droplets_chunk_reply(true, '');
}

if ($action === 'complete') {
    clearstatcache(true, $upload['path']);
    if ((int) $upload['next'] !== (int) $upload['chunks'] || !is_file($upload['path']) || (int) filesize($upload['path']) !== (int) $upload['size']) {
        droplets_chunk_reply(false, $DR_TEXT['CHUNK_INCOMPLETE'], array(), 409);
    }
    $result = importDropletFromZip($upload['path'], $baseDir . '/' . $id, true);
    @unlink($upload['path']);
    unset($_SESSION['droplets_chunk_uploads'][$id]);
    if (!empty($result['errors'])) droplets_chunk_reply(false, $DR_TEXT['IMPORT_ERRORS'], array('result' => $result), 422);
    droplets_chunk_reply(true, $result['count'] . ' ' . $DR_TEXT['IMPORTED'], array('result' => $result));
}

droplets_chunk_reply(false, $DR_TEXT['CHUNK_UNKNOWN_ACTION'], array(), 400);
