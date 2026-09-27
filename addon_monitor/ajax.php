<?php
ob_start();
require_once dirname(__DIR__, 2).'/config.php';
require_once WB_PATH.'/framework/class.admin.php';
$admin = new admin('admintools', 'admintools', false);
require_once __DIR__.'/Language.php';
require_once __DIR__.'/functions.php';
if (ob_get_level()) ob_end_clean();

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$reply = static function ($success, $message, $enabled = false, $status = 200) use ($admin) {
    http_response_code($status);
    echo json_encode(array('success'=>(bool)$success,'message'=>(string)$message,'enabled'=>(bool)$enabled,'ftan'=>$admin->getFTAN()), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT);
    exit;
};

if (!$admin->get_permission('admintools')) $reply(false, addon_monitor_t('permission_denied'), false, 403);
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || ($_POST['addon_monitor_action'] ?? '') !== 'toggle') $reply(false, addon_monitor_t('invalid_request'), false, 405);
if (!defined('WBCE_VERSION') || version_compare((string)WBCE_VERSION, '1.7.0', '<') || !function_exists('wbce_set_addon_active')) $reply(false, addon_monitor_t('activation_requires_wbce17'), false, 409);
if (!$admin->checkFTAN()) $reply(false, addon_monitor_t('security_failed'), false, 403);

try {
    $directory = isset($_POST['module_directory']) ? (string)$_POST['module_directory'] : '';
    $enabled = !empty($_POST['enabled']);
    if (!preg_match('/^[a-z0-9][a-z0-9_-]{0,79}$/i', $directory)) throw new InvalidArgumentException(addon_monitor_t('invalid_directory'));
    if ($directory === 'addon_monitor' && !$enabled) throw new RuntimeException(addon_monitor_t('self_disable'));
    $escaped = $database->escapeString($directory);
    if ((int)addon_monitor_db_value($database, "SELECT COUNT(*) FROM `{TP}addons` WHERE `type`='module' AND `directory`='$escaped'") !== 1) throw new RuntimeException(addon_monitor_t('module_not_found'));
    wbce_set_addon_active($directory, $enabled, 'module');
    $stored = (int)addon_monitor_db_value($database, "SELECT `active` FROM `{TP}addons` WHERE `type`='module' AND `directory`='$escaped'");
    if ($stored !== ($enabled ? 1 : 0)) throw new RuntimeException(addon_monitor_t('status_not_confirmed'));
    $reply(true, $stored ? addon_monitor_t('activated') : addon_monitor_t('deactivated'), (bool)$stored);
} catch (Throwable $error) {
    $reply(false, $error->getMessage(), !empty($_POST['enabled']), 400);
}
