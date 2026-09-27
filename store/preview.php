<?php
require '../../config.php';
require_once WB_PATH.'/framework/class.admin.php';
$admin = new admin('Admintools', 'admintools', false, false);
require_once __DIR__.'/HttpClient.php';

function wbce_store_preview_fallback()
{
    $path = __DIR__.'/assets/template-preview-fallback.jpg';
    if (!is_file($path)) { http_response_code(404); exit; }
    header('Content-Type: image/jpeg');
    header('Content-Length: '.filesize($path));
    header('Cache-Control: private, max-age=3600');
    header('X-Content-Type-Options: nosniff');
    readfile($path);
    exit;
}

$sourceId = isset($_GET['source_id']) ? (int)$_GET['source_id'] : 0;
$packageId = isset($_GET['package_id']) ? (int)$_GET['package_id'] : 0;
$result = $database->query('SELECT `catalog_url`,`access_token` FROM `{TP}mod_store_sources` WHERE `id`='.$sourceId.' AND `active`=1 LIMIT 1');
$source = $result ? $result->fetchRow(MYSQLI_ASSOC) : null;
if (!$source || $packageId < 1) wbce_store_preview_fallback();

try {
    $http = new WbceRepositoryHttpClient();
    $catalog = $http->json($source['catalog_url'], isset($source['access_token']) ? $source['access_token'] : '');
    $previewUrl = '';
    foreach ($catalog['packages'] as $package) {
        if (isset($package['id']) && (int)$package['id'] === $packageId && !empty($package['preview_url'])) {
            $previewUrl = (string)$package['preview_url'];
            break;
        }
    }
    if ($previewUrl === '' || parse_url($previewUrl, PHP_URL_SCHEME) !== 'https'
        || strcasecmp((string)parse_url($previewUrl, PHP_URL_HOST), (string)parse_url($source['catalog_url'], PHP_URL_HOST)) !== 0) {
        throw new RuntimeException('Preview unavailable');
    }
    $image = $http->image($previewUrl, isset($source['access_token']) ? $source['access_token'] : '');
    header('Content-Type: '.$image['mime']);
    header('Content-Length: '.strlen($image['content']));
    header('Cache-Control: private, max-age=3600');
    header('X-Content-Type-Options: nosniff');
    echo $image['content'];
} catch (Throwable $error) {
    wbce_store_preview_fallback();
}
