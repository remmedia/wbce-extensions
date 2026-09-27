<?php
require_once '../../config.php';
require_once WB_PATH . '/framework/class.admin.php';
$admin = new admin('Admintools', 'admintools', false, false);
$languageFile = __DIR__ . '/languages/' . (defined('LANGUAGE') ? LANGUAGE : 'EN') . '.php';
require is_file($languageFile) ? $languageFile : __DIR__ . '/languages/EN.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function wbstats_settings_reply($ok, $message, $status, $admin)
{
    http_response_code((int) $status);
    echo json_encode(array('ok'=>(bool) $ok,'message'=>(string) $message,'ftan'=>$admin->getFTAN()), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    exit;
}

if (!$admin->is_authenticated() || !$admin->get_permission('admintools')) wbstats_settings_reply(false, $WS['SETTINGS_FAILED'], 403, $admin);
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !$admin->checkFTAN()) wbstats_settings_reply(false, $WS['SETTINGS_FAILED'], 403, $admin);
require_once __DIR__ . '/class.stats.php';
$stats = new stats(false);
$raw = isset($_POST['ips']) && is_scalar($_POST['ips']) ? (string) $_POST['ips'] : '';
$items = preg_split('/[\s,|]+/', $raw, -1, PREG_SPLIT_NO_EMPTY);
$valid = array();
foreach ($items as $item) {
    if (filter_var($item, FILTER_VALIDATE_IP) !== false) $valid[$item] = $item;
}
$stats->setIgnores(array_values($valid));
$trackingEnabled = !empty($_POST['tracking_enabled']) ? '1' : '0';
$cfgTable = TABLE_PREFIX.'mod_wbstats_cfg';
$database->query("DELETE FROM `$cfgTable` WHERE `type`='system' AND `name`='enabled'");
$database->query("INSERT INTO `$cfgTable` (`type`,`name`,`value`) VALUES ('system','enabled','$trackingEnabled')");
wbstats_settings_reply(true, $WS['SETTINGS_SAVED'], 200, $admin);
