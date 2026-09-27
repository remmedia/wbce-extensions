<?php

header('Content-Type: application/json; charset=UTF-8');
require_once dirname(__DIR__, 3).'/config.php';
require_once WB_PATH.'/framework/class.admin.php';
$admin = new admin('Pages', 'pages_modify', false, false);

function nwi_form_chunk_response($ok, $message, array $extra = array(), $status = 200)
{
    http_response_code($status);
    echo json_encode(array_merge(array('ok' => (bool) $ok, 'message' => (string) $message), $extra), JSON_UNESCAPED_UNICODE);
    exit;
}

require_once __DIR__.'/../functions.inc.php';
if (!$admin->is_authenticated() || !$admin->get_permission('news_img', 'module')) {
    nwi_form_chunk_response(false, $MOD_NEWS_IMG['UPLOAD_FORBIDDEN'], array(), 403);
}
$token = isset($_POST['token']) && is_scalar($_POST['token']) ? (string) $_POST['token'] : '';
if (empty($_SESSION['nwi_form_chunk_token']) || !hash_equals((string) $_SESSION['nwi_form_chunk_token'], $token)) {
    nwi_form_chunk_response(false, $MOD_NEWS_IMG['UPLOAD_SESSION_EXPIRED'], array(), 403);
}

$baseDir = WB_PATH.'/temp/nwi-form-chunks';
if (!is_dir($baseDir) && !make_dir($baseDir)) {
    nwi_form_chunk_response(false, $MOD_NEWS_IMG['UPLOAD_STORAGE_FAILED'], array(), 500);
}
foreach (glob($baseDir.'/*.part') ?: array() as $oldPart) {
    if (is_file($oldPart) && filemtime($oldPart) < time() - 86400) { @unlink($oldPart); }
}
$action = isset($_POST['action']) && is_scalar($_POST['action']) ? (string) $_POST['action'] : '';
if ($action === 'init') {
    $name = isset($_POST['name']) && is_scalar($_POST['name']) ? basename((string) $_POST['name']) : '';
    $size = filter_var($_POST['size'] ?? null, FILTER_VALIDATE_INT);
    $chunks = filter_var($_POST['chunks'] ?? null, FILTER_VALIDATE_INT);
    $sectionId = filter_var($_POST['section_id'] ?? null, FILTER_VALIDATE_INT);
    $kind = isset($_POST['kind']) && in_array($_POST['kind'], array('preview', 'group'), true) ? $_POST['kind'] : '';
    $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    $settings = $sectionId ? mod_nwi_settings_get((int) $sectionId) : array();
    $configuredLimit = isset($settings['imgmaxsize']) ? (int) $settings['imgmaxsize'] : 0;
    $limit = $configuredLimit > 0 ? min($configuredLimit, 512 * 1024 * 1024) : 512 * 1024 * 1024;
    if ($kind === '' || $name === '' || $size === false || $size < 1 || $size > $limit || $chunks === false || $chunks < 1
        || !in_array($extension, $allowed_suffixes, true)) {
        nwi_form_chunk_response(false, sprintf($MOD_NEWS_IMG['UPLOAD_INVALID_FILE'], mod_nwi_byte_convert($limit)), array(), 400);
    }
    $uploadId = bin2hex(random_bytes(20));
    $_SESSION['nwi_form_chunk_uploads'][$uploadId] = array(
        'name' => $name, 'size' => $size, 'chunks' => $chunks, 'next' => 0,
        'section_id' => (int) $sectionId, 'kind' => $kind, 'ready' => false,
    );
    nwi_form_chunk_response(true, '', array('upload_id' => $uploadId));
}

$uploadId = isset($_POST['upload_id']) && is_scalar($_POST['upload_id']) ? (string) $_POST['upload_id'] : '';
$upload = $_SESSION['nwi_form_chunk_uploads'][$uploadId] ?? null;
if (!is_array($upload) || !preg_match('/^[a-f0-9]{40}$/', $uploadId)) {
    nwi_form_chunk_response(false, $MOD_NEWS_IMG['UPLOAD_UNKNOWN'], array(), 404);
}
$partFile = $baseDir.'/'.$uploadId.'.part';
if ($action === 'chunk') {
    $index = filter_var($_POST['index'] ?? null, FILTER_VALIDATE_INT);
    if ($index === false || $index !== (int) $upload['next'] || empty($_FILES['chunk']['tmp_name']) || !is_uploaded_file($_FILES['chunk']['tmp_name'])) {
        nwi_form_chunk_response(false, $MOD_NEWS_IMG['UPLOAD_SEQUENCE_ERROR'], array(), 409);
    }
    $input = fopen($_FILES['chunk']['tmp_name'], 'rb');
    $output = fopen($partFile, $index === 0 ? 'wb' : 'ab');
    $copied = $input && $output ? stream_copy_to_stream($input, $output) : false;
    if (is_resource($input)) { fclose($input); }
    if (is_resource($output)) { fclose($output); }
    if ($copied === false) { nwi_form_chunk_response(false, $MOD_NEWS_IMG['UPLOAD_STORAGE_FAILED'], array(), 500); }
    $_SESSION['nwi_form_chunk_uploads'][$uploadId]['next'] = $index + 1;
    nwi_form_chunk_response(true, '');
}
if ($action !== 'complete' || (int) $upload['next'] !== (int) $upload['chunks'] || !is_file($partFile)
    || filesize($partFile) !== (int) $upload['size'] || @getimagesize($partFile) === false) {
    nwi_form_chunk_response(false, $MOD_NEWS_IMG['UPLOAD_INCOMPLETE'], array(), 400);
}
$_SESSION['nwi_form_chunk_uploads'][$uploadId]['ready'] = true;
nwi_form_chunk_response(true, $MOD_NEWS_IMG['UPLOAD_COMPLETE'], array('upload_id' => $uploadId));
