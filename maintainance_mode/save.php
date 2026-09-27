<?php
ob_start();
function maintenance_reply($ok,$message,$status=200,$extra=array())
{
    http_response_code((int)$status);
    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('X-Content-Type-Options: nosniff');
    $json=json_encode(array_merge(array('success'=>(bool)$ok,'message'=>(string)$message),is_array($extra)?$extra:array()),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT);
    echo is_string($json)?$json:'{"success":false}';
    exit;
}

$language = strtoupper(substr((string)($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'EN'), 0, 2));
if (!preg_match('/^[A-Z]{2}$/D',$language)) $language='EN';
require __DIR__.'/languages/EN.php';
$fallback=$MOD_MAINTAINANCE;
if($language!=='EN'&&is_readable(__DIR__.'/languages/'.$language.'.php'))require __DIR__.'/languages/'.$language.'.php';
$MOD_MAINTAINANCE=array_merge($fallback,isset($MOD_MAINTAINANCE)&&is_array($MOD_MAINTAINANCE)?$MOD_MAINTAINANCE:array());

$config=dirname(__DIR__,2).'/config.php';
if(!is_readable($config)){if(ob_get_level())ob_end_clean();maintenance_reply(false,$MOD_MAINTAINANCE['CONFIGURATION_UNAVAILABLE'],500);}
require_once $config;
require_once WB_PATH.'/framework/Admin.php';
$language=defined('LANGUAGE')?strtoupper((string)LANGUAGE):'EN';
require __DIR__.'/languages/EN.php';
$fallback=$MOD_MAINTAINANCE;
if($language!=='EN'&&preg_match('/^[A-Z]{2}$/D',$language)&&is_readable(__DIR__.'/languages/'.$language.'.php'))require __DIR__.'/languages/'.$language.'.php';
$MOD_MAINTAINANCE=array_merge($fallback,isset($MOD_MAINTAINANCE)&&is_array($MOD_MAINTAINANCE)?$MOD_MAINTAINANCE:array());

$admin=new admin('admintools','admintools',false);
if(ob_get_level())ob_end_clean();
if(!$admin->is_authenticated()||!$admin->get_permission('maintainance_mode','module'))maintenance_reply(false,$MOD_MAINTAINANCE['FORBIDDEN'],403);
$requestMethod=isset($_SERVER['REQUEST_METHOD'])&&is_string($_SERVER['REQUEST_METHOD'])?$_SERVER['REQUEST_METHOD']:'';
if($requestMethod!=='POST'||!$admin->checkFTAN())maintenance_reply(false,$MOD_MAINTAINANCE['SECURITY_ERROR'],403,array('ftan'=>$admin->getFTAN()));

$enabled=isset($_POST['maintMode'])&&is_scalar($_POST['maintMode'])&&(string)$_POST['maintMode']==='1';
$previous=(string)Settings::Get('wb_maintainance_mode')==='1'||Settings::Get('wb_maintainance_mode')===true||Settings::Get('wb_maintainance_mode')==='btrueb';
$result=Settings::Set('wb_maintainance_mode',$enabled?'1':'0');
if($result!==false&&$result!==null&&$result!=='')maintenance_reply(false,$MOD_MAINTAINANCE['SAVE_FAILED'],500,array('enabled'=>$previous,'ftan'=>$admin->getFTAN()));
$stored=(string)Settings::Get('wb_maintainance_mode')==='1';
if($stored!==$enabled)maintenance_reply(false,$MOD_MAINTAINANCE['SAVE_NOT_CONFIRMED'],500,array('enabled'=>$stored,'ftan'=>$admin->getFTAN()));
if($stored!==$previous){if(!function_exists('wbce_do_action')){$bridge=WB_PATH.'/modules/wbce_hook_bridge/preinit.php';if(is_file($bridge))require_once $bridge;}if(function_exists('wbce_do_action'))wbce_do_action($stored?'system.maintenance.started':'system.maintenance.completed',array('old'=>$previous,'new'=>$stored,'source'=>'maintainance_mode'));}
maintenance_reply(true,$stored?$MOD_MAINTAINANCE['ENABLED_SAVED']:$MOD_MAINTAINANCE['DISABLED_SAVED'],200,array('enabled'=>$stored,'ftan'=>$admin->getFTAN()));
