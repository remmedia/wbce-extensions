<?php
require_once '../../config.php';
require_once WB_PATH . '/framework/class.admin.php';
$admin = new admin('Admintools', 'admintools', false, false);
$languageFile = __DIR__ . '/languages/' . (defined('LANGUAGE') ? LANGUAGE : 'EN') . '.php';
if (defined('LANGUAGE') && LANGUAGE !== 'EN') require __DIR__ . '/languages/EN.php';
require is_file($languageFile) ? $languageFile : __DIR__ . '/languages/EN.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
$reply = static function ($ok, $message, $status = 200) use ($admin) {
    http_response_code((int) $status);
    echo json_encode(array('ok'=>(bool)$ok,'message'=>(string)$message,'ftan'=>$admin->getFTAN()), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    exit;
};
if (!$admin->is_authenticated() || !$admin->get_permission('admintools')) $reply(false, $TOOL_TEXT['SETTINGS_FAILED'], 403);
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !$admin->checkFTAN()) $reply(false, $TOOL_TEXT['SETTINGS_FAILED'], 403);
$integer = static function ($name) { return max(0, min(10000, (int) ($_POST[$name] ?? 0))); };
$plain = static function ($name) { return trim(strip_tags(is_scalar($_POST[$name] ?? null) ? (string) $_POST[$name] : '')); };
$settings = array(
    'iTitleCount'=>array('use'=>isset($_POST['iTitleCount_use'])?1:0,'optimum'=>$integer('iTitleCount_optimum'),'minimum'=>$integer('iTitleCount_minimum')),
    'iDescriptionCount'=>array('use'=>isset($_POST['iDescriptionCount_use'])?1:0,'optimum'=>$integer('iDescriptionCount_optimum'),'minimum'=>$integer('iDescriptionCount_minimum')),
    'keywordsConfig'=>array('use'=>isset($_POST['keywordsConfig_use'])?1:0,'wordReplace'=>$plain('keywordsConfig_wordReplace')),
    'rewriteUrl'=>array('use'=>isset($_POST['rewriteUrl_use'])?1:0,'dbString'=>preg_replace('/[^A-Za-z0-9_]/','',$plain('rewriteUrl_dbString'))),
    'bUseRemainingChars'=>isset($_POST['bUseRemainingChars'])?1:0,
    'bUseFlags'=>isset($_POST['bUseFlags'])?1:0,
);
$json = json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$database->query("UPDATE `{TP}mod_page_seo_tool` SET `settings_json`='".$database->escapeString($json)."'");
if ($database->is_error()) $reply(false, $database->get_error(), 500);
$reply(true, $TOOL_TEXT['SETTINGS_SAVED']);
