<?php
ob_start();
function jsadmin_reply($ok, $message, $status = 200, $extra = array())
{
    http_response_code((int)$status);
    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('X-Content-Type-Options: nosniff');
    $json=json_encode(array_merge(array('success'=>(bool)$ok,'message'=>(string)$message), is_array($extra)?$extra:array()), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT);
    echo is_string($json)?$json:'{"success":false}';
    exit;
}

$language = strtoupper(substr((string)($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'EN'), 0, 2));
if (!preg_match('/^[A-Z]{2}$/D', $language)) $language = 'EN';
require __DIR__.'/languages/EN.php';
$fallback = $MOD_JSADMIN;
if ($language !== 'EN' && is_readable(__DIR__.'/languages/'.$language.'.php')) require __DIR__.'/languages/'.$language.'.php';
$MOD_JSADMIN = array_merge($fallback, isset($MOD_JSADMIN)&&is_array($MOD_JSADMIN)?$MOD_JSADMIN:array());

$config = dirname(__DIR__, 2).'/config.php';
if (!is_readable($config)) { if (ob_get_level()) ob_end_clean(); jsadmin_reply(false, $MOD_JSADMIN['TXT_CONFIGURATION_UNAVAILABLE'], 500); }
require_once $config;
require_once WB_PATH.'/framework/class.admin.php';
require_once __DIR__.'/jsadmin.php';
$language = defined('LANGUAGE') ? strtoupper((string)LANGUAGE) : 'EN';
require __DIR__.'/languages/EN.php';
$fallback = $MOD_JSADMIN;
if ($language !== 'EN' && preg_match('/^[A-Z]{2}$/D',$language) && is_readable(__DIR__.'/languages/'.$language.'.php')) require __DIR__.'/languages/'.$language.'.php';
$MOD_JSADMIN = array_merge($fallback, isset($MOD_JSADMIN)&&is_array($MOD_JSADMIN)?$MOD_JSADMIN:array());

$admin = new admin('admintools','admintools',false);
if (ob_get_level()) ob_end_clean();
if (!$admin->is_authenticated() || !$admin->get_permission('jsadmin','module')) jsadmin_reply(false,$MOD_JSADMIN['TXT_FORBIDDEN'],403);
$requestMethod=isset($_SERVER['REQUEST_METHOD'])&&is_string($_SERVER['REQUEST_METHOD'])?$_SERVER['REQUEST_METHOD']:'';
if ($requestMethod !== 'POST' || !$admin->checkFTAN()) jsadmin_reply(false,$MOD_JSADMIN['TXT_SECURITY_ERROR'],403,array('ftan'=>$admin->getFTAN()));

$state = array();
$failed = false;
foreach (array('persist_order','ajax_order_pages','ajax_order_sections') as $name) {
    $value = isset($_POST[$name]) && is_scalar($_POST[$name]) && (string)$_POST[$name] === '1' ? 1 : 0;
    if (!save_setting('mod_jsadmin_'.$name, $value)) $failed = true;
    $stored = (int)get_setting('mod_jsadmin_'.$name, -1);
    if ($stored !== $value) $failed = true;
    $state[$name] = $stored === 1;
}
if ($failed) jsadmin_reply(false,$MOD_JSADMIN['TXT_SAVE_FAILED'],500,array('state'=>$state,'ftan'=>$admin->getFTAN()));
jsadmin_reply(true,$MOD_JSADMIN['TXT_SAVED'],200,array('state'=>$state,'ftan'=>$admin->getFTAN()));
