<?php
defined('WB_PATH') or die('No direct access');
$CAPTCHA_PROVIDER=array(); require __DIR__.'/languages/EN.php';
$captchaCapLanguage=defined('LANGUAGE')?preg_replace('/[^A-Z]/','',strtoupper((string)LANGUAGE)):'EN';
if($captchaCapLanguage!=='EN'&&is_file(__DIR__.'/languages/'.$captchaCapLanguage.'.php')){$captchaCapEnglish=$CAPTCHA_PROVIDER;require __DIR__.'/languages/'.$captchaCapLanguage.'.php';$CAPTCHA_PROVIDER=array_merge($captchaCapEnglish,$CAPTCHA_PROVIDER);}
$captchaCapNativeHooks=(function_exists('wbce_add_action')&&function_exists('wbce_add_filter'))||(defined('WBCE_VERSION')&&version_compare((string)WBCE_VERSION,'1.7.0','>='));
$captchaCapBridgeRow=null;
if(!$captchaCapNativeHooks&&isset($database)&&is_object($database)&&is_callable([$database,'query'])){$captchaCapBridgeResult=$database->query("SELECT `version` FROM `{TP}addons` WHERE `type`='module' AND `directory`='wbce_hook_bridge' LIMIT 1");if(is_object($captchaCapBridgeResult)&&is_callable([$captchaCapBridgeResult,'fetchRow']))$captchaCapBridgeRow=$captchaCapBridgeResult->fetchRow(defined('MYSQLI_ASSOC')?MYSQLI_ASSOC:1);elseif(is_object($captchaCapBridgeResult)&&is_callable([$captchaCapBridgeResult,'fetch_assoc']))$captchaCapBridgeRow=$captchaCapBridgeResult->fetch_assoc();}
$captchaCapHooksAvailable=$captchaCapNativeHooks||(is_array($captchaCapBridgeRow)&&version_compare((string)($captchaCapBridgeRow['version']??''),'1.2.0','>='));
$PRECHECK = array(
    'WBCE_VERSION' => array('VERSION' => '1.6.8', 'OPERATOR' => '>='),
    'PHP_VERSION' => array('VERSION' => '8.2.0', 'OPERATOR' => '>='),
    'WB_ADDONS' => array('captcha_control' => array('VERSION' => '3.1.33', 'OPERATOR' => '>=')),
    'CUSTOM_CHECKS'=>array($CAPTCHA_PRECHECK_LABEL=>array('REQUIRED'=>$CAPTCHA_PROVIDER['hooks_required'],'ACTUAL'=>$captchaCapHooksAvailable?$CAPTCHA_PROVIDER['available']:$CAPTCHA_PROVIDER['unavailable'],'STATUS'=>$captchaCapHooksAvailable)),
);
