<?php

header('Content-Type: application/json; charset=UTF-8');
require_once dirname(__DIR__, 3).'/config.php';
require_once WB_PATH.'/framework/class.admin.php';

$admin = new admin('Pages', 'pages_modify', false, false);
function nwi_chunk_response($ok, $message, array $extra = array(), $status = 200)
{
    http_response_code($status);
    echo json_encode(array_merge(array('ok' => (bool) $ok, 'message' => (string) $message), $extra), JSON_UNESCAPED_UNICODE);
    exit;
}

require_once __DIR__.'/../functions.inc.php';
if (!$admin->is_authenticated() || !$admin->get_permission('news_img', 'module')) {
    nwi_chunk_response(false, $MOD_NEWS_IMG['UPLOAD_FORBIDDEN'], array(), 403);
}

$postId = isset($_GET['post_id']) ? (int) $_GET['post_id'] : 0;
if ($postId < 1) {
    nwi_chunk_response(false, $MOD_NEWS_IMG['UPLOAD_INVALID_REQUEST'], array(), 400);
}
$post = $database->query("SELECT `section_id` FROM `{TP}mod_news_img_posts` WHERE `post_id`={$postId}");
$postRow = $post ? $post->fetchRow() : null;
if (!$postRow) {
    nwi_chunk_response(false, $MOD_NEWS_IMG['UPLOAD_INVALID_REQUEST'], array(), 404);
}
$settings = mod_nwi_settings_get((int) $postRow['section_id']);
$absoluteLimit = 512 * 1024 * 1024;
$configuredLimit = (int) $settings['imgmaxsize'];
$maximumSize = $configuredLimit > 0 ? min($configuredLimit, $absoluteLimit) : $absoluteLimit;

$token = isset($_POST['token']) && is_scalar($_POST['token']) ? (string) $_POST['token'] : '';
if (empty($_SESSION['nwi_chunk_token']) || !hash_equals((string) $_SESSION['nwi_chunk_token'], $token)) {
    nwi_chunk_response(false, $MOD_NEWS_IMG['UPLOAD_SESSION_EXPIRED'], array(), 403);
}

$baseDir = WB_PATH.'/temp/nwi-chunks';
if (!is_dir($baseDir) && !make_dir($baseDir)) {
    nwi_chunk_response(false, $MOD_NEWS_IMG['UPLOAD_STORAGE_FAILED'], array(), 500);
}
$action = isset($_POST['action']) && is_scalar($_POST['action']) ? (string) $_POST['action'] : '';

if ($action === 'init') {
    $name = isset($_POST['name']) && is_scalar($_POST['name']) ? basename((string) $_POST['name']) : '';
    $size = filter_var($_POST['size'] ?? null, FILTER_VALIDATE_INT);
    $chunks = filter_var($_POST['chunks'] ?? null, FILTER_VALIDATE_INT);
    $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    if ($name === '' || $size === false || $size < 1 || $size > $maximumSize || $chunks === false || $chunks < 1
        || !in_array($extension, $allowed_suffixes, true)) {
        nwi_chunk_response(false, sprintf($MOD_NEWS_IMG['UPLOAD_INVALID_FILE'], mod_nwi_byte_convert($maximumSize)), array(), 400);
    }
    $uploadId = bin2hex(random_bytes(20));
    $_SESSION['nwi_chunk_uploads'][$uploadId] = array('name' => $name, 'size' => $size, 'chunks' => $chunks, 'next' => 0, 'post_id' => $postId);
    nwi_chunk_response(true, '', array('upload_id' => $uploadId));
}

$uploadId = isset($_POST['upload_id']) && is_scalar($_POST['upload_id']) ? (string) $_POST['upload_id'] : '';
$upload = $_SESSION['nwi_chunk_uploads'][$uploadId] ?? null;
if (!is_array($upload) || !preg_match('/^[a-f0-9]{40}$/', $uploadId) || (int) $upload['post_id'] !== $postId) {
    nwi_chunk_response(false, $MOD_NEWS_IMG['UPLOAD_UNKNOWN'], array(), 404);
}
$partFile = $baseDir.'/'.$uploadId.'.part';

if ($action === 'chunk') {
    $index = filter_var($_POST['index'] ?? null, FILTER_VALIDATE_INT);
    if ($index === false || $index !== (int) $upload['next'] || empty($_FILES['chunk']['tmp_name']) || !is_uploaded_file($_FILES['chunk']['tmp_name'])) {
        nwi_chunk_response(false, $MOD_NEWS_IMG['UPLOAD_SEQUENCE_ERROR'], array(), 409);
    }
    $input = fopen($_FILES['chunk']['tmp_name'], 'rb');
    $output = fopen($partFile, $index === 0 ? 'wb' : 'ab');
    if (!$input || !$output || stream_copy_to_stream($input, $output) === false) {
        if (is_resource($input)) { fclose($input); }
        if (is_resource($output)) { fclose($output); }
        nwi_chunk_response(false, $MOD_NEWS_IMG['UPLOAD_STORAGE_FAILED'], array(), 500);
    }
    fclose($input);
    fclose($output);
    $_SESSION['nwi_chunk_uploads'][$uploadId]['next'] = $index + 1;
    nwi_chunk_response(true, '');
}

if ($action !== 'complete' || (int) $upload['next'] !== (int) $upload['chunks'] || !is_file($partFile)
    || filesize($partFile) !== (int) $upload['size'] || @getimagesize($partFile) === false) {
    nwi_chunk_response(false, $MOD_NEWS_IMG['UPLOAD_INCOMPLETE'], array(), 400);
}

$extension = strtolower(pathinfo($upload['name'], PATHINFO_EXTENSION));
$imageName = strtolower(media_filename($upload['name']));
$fileDir = $mod_nwi_file_dir.$postId.'/';
$thumbDir = $fileDir.'thumb/';
mod_nwi_img_makedir($fileDir);
$baseName = pathinfo($imageName, PATHINFO_FILENAME);
$candidate = $imageName;
$counter = 1;
while (is_file($fileDir.$candidate)) {
    $candidate = $baseName.'_'.$counter++.'.'.$extension;
}
$target = $fileDir.$candidate;
if (!rename($partFile, $target)) {
    nwi_chunk_response(false, $MOD_NEWS_IMG['UPLOAD_STORAGE_FAILED'], array(), 500);
}

$maxWidth = max(1, (int) $settings['imgmaxwidth']);
$maxHeight = max(1, (int) $settings['imgmaxheight']);
$crop = $settings['crop_preview'] === 'Y' ? 1 : 0;
[$width, $height] = getimagesize($target);
if ($width > $maxWidth || $height > $maxHeight) {
    $resizeResult = mod_nwi_image_resize($target, $target, $maxWidth, $maxHeight, $crop);
    if ($resizeResult !== true) {
        @unlink($target);
        nwi_chunk_response(false, (string) $resizeResult, array(), 400);
    }
}
[$thumbWidth, $thumbHeight] = array_pad(explode('x', (string) $settings['imgthumbsize'], 2), 2, 100);
$thumbResult = mod_nwi_image_resize($target, $thumbDir.$candidate, (int) $thumbWidth, (int) $thumbHeight, $crop);
if ($thumbResult !== true) {
    @unlink($target);
    nwi_chunk_response(false, (string) $thumbResult, array(), 400);
}
$order = new order(TABLE_PREFIX.'mod_news_img_img', 'position', 'id', 'post_id');
$position = $order->get_new($postId);
$safeName = mod_nwi_escapeString($candidate);
$database->query("INSERT INTO `{TP}mod_news_img_img` (`picname`,`post_id`,`position`) VALUES ('{$safeName}',{$postId},{$position})");
unset($_SESSION['nwi_chunk_uploads'][$uploadId]);
nwi_chunk_response(true, $MOD_NEWS_IMG['UPLOAD_COMPLETE'], array('name' => $candidate));
