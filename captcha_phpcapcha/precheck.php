<?php
defined('WB_PATH') or die('No direct access');
$CAPTCHA_PROVIDER=array();require __DIR__.'/languages/EN.php';$captchaPhpLanguage=defined('LANGUAGE')?preg_replace('/[^A-Z]/','',strtoupper((string)LANGUAGE)):'EN';if($captchaPhpLanguage!=='EN'&&is_file(__DIR__.'/languages/'.$captchaPhpLanguage.'.php')){$captchaPhpEnglish=$CAPTCHA_PROVIDER;require __DIR__.'/languages/'.$captchaPhpLanguage.'.php';$CAPTCHA_PROVIDER=array_merge($captchaPhpEnglish,$CAPTCHA_PROVIDER);}
$captchaPhpNativeHooks=(function_exists('wbce_add_action')&&function_exists('wbce_add_filter'))||(defined('WBCE_VERSION')&&version_compare((string)WBCE_VERSION,'1.7.0','>='));$captchaPhpBridgeRow=null;
if(!$captchaPhpNativeHooks&&isset($database)&&is_object($database)&&is_callable([$database,'query'])){$captchaPhpBridgeResult=$database->query("SELECT `version` FROM `{TP}addons` WHERE `type`='module' AND `directory`='wbce_hook_bridge' LIMIT 1");if(is_object($captchaPhpBridgeResult)&&is_callable([$captchaPhpBridgeResult,'fetchRow']))$captchaPhpBridgeRow=$captchaPhpBridgeResult->fetchRow(defined('MYSQLI_ASSOC')?MYSQLI_ASSOC:1);elseif(is_object($captchaPhpBridgeResult)&&is_callable([$captchaPhpBridgeResult,'fetch_assoc']))$captchaPhpBridgeRow=$captchaPhpBridgeResult->fetch_assoc();}
$captchaPhpHooksAvailable=$captchaPhpNativeHooks||(is_array($captchaPhpBridgeRow)&&version_compare((string)($captchaPhpBridgeRow['version']??''),'1.2.0','>='));
$PRECHECK = array(
    'WBCE_VERSION' => array('VERSION' => '1.6.8', 'OPERATOR' => '>='),
    'PHP_VERSION' => array('VERSION' => '8.2.0', 'OPERATOR' => '>='),
    'PHP_EXTENSIONS' => array('gd'),
    'WB_ADDONS' => array('captcha_control' => array('VERSION' => '3.1.33', 'OPERATOR' => '>=')),
    'CUSTOM_CHECKS'=>array($CAPTCHA_PRECHECK_LABEL=>array('REQUIRED'=>$CAPTCHA_PROVIDER['hooks_required'],'ACTUAL'=>$captchaPhpHooksAvailable?$CAPTCHA_PROVIDER['available']:$CAPTCHA_PROVIDER['unavailable'],'STATUS'=>$captchaPhpHooksAvailable)),
);
