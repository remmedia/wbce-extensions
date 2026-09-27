<?php
require_once '../../config.php';
require_once WB_PATH.'/framework/class.admin.php';
require_once __DIR__.'/src/Config.php';
require_once __DIR__.'/Language.php';

while (ob_get_level() > 0 && !ob_end_clean()) break;
ob_start();
$admin = new admin('admintools', 'admintools', false, false);
$texts = accessibility_tools_texts();
$status = 200;

try {
    if (!$admin->is_authenticated() || !$admin->get_permission('accessibility_tools', 'module')) {
        $status = 403;
        throw new RuntimeException($texts['error']);
    }
    $requestMethod = isset($_SERVER['REQUEST_METHOD']) && is_string($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';
    $action = isset($_POST['accessibility_tools_action']) && is_string($_POST['accessibility_tools_action']) ? $_POST['accessibility_tools_action'] : '';
    if ($requestMethod !== 'POST'
        || $action !== 'save'
        || !$admin->checkFTAN()) {
        $status = 403;
        throw new RuntimeException($texts['error']);
    }
    $config = WbceAccessibilityToolsConfig::save($_POST, $database);
    $payload = array('ok'=>true, 'message'=>$texts['saved'], 'config'=>$config, 'ftan'=>$admin->getFTAN());
} catch (Throwable $error) {
    if ($status < 400) $status = 400;
    $known = in_array($error->getMessage(), array($texts['database_unavailable'], $texts['write_failed']), true);
    $payload = array('ok'=>false, 'message'=>$known ? $error->getMessage() : $texts['error'], 'ftan'=>method_exists($admin, 'getFTAN') ? $admin->getFTAN() : '');
}

while (ob_get_level() > 0 && !ob_end_clean()) break;
http_response_code($status);
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('X-Content-Type-Options: nosniff');
$encoded = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
echo is_string($encoded) ? $encoded : '{"ok":false}';
