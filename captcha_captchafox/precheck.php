<?php
defined('WB_PATH') or die('No direct access');
$CAPTCHA_PROVIDER=array();require __DIR__.'/languages/EN.php';$captchaFoxLanguage=defined('LANGUAGE')?preg_replace('/[^A-Z]/','',strtoupper((string)LANGUAGE)):'EN';if($captchaFoxLanguage!=='EN'&&is_file(__DIR__.'/languages/'.$captchaFoxLanguage.'.php')){$captchaFoxEnglish=$CAPTCHA_PROVIDER;require __DIR__.'/languages/'.$captchaFoxLanguage.'.php';$CAPTCHA_PROVIDER=array_merge($captchaFoxEnglish,$CAPTCHA_PROVIDER);}
$captchaFoxNativeHooks=(function_exists('wbce_add_action')&&function_exists('wbce_add_filter'))||(defined('WBCE_VERSION')&&version_compare((string)WBCE_VERSION,'1.7.0','>='));$captchaFoxBridgeRow=null;
if(!$captchaFoxNativeHooks&&isset($database)&&is_object($database)&&is_callable([$database,'query'])){$captchaFoxBridgeResult=$database->query("SELECT `version` FROM `{TP}addons` WHERE `type`='module' AND `directory`='wbce_hook_bridge' LIMIT 1");if(is_object($captchaFoxBridgeResult)&&is_callable([$captchaFoxBridgeResult,'fetchRow']))$captchaFoxBridgeRow=$captchaFoxBridgeResult->fetchRow(defined('MYSQLI_ASSOC')?MYSQLI_ASSOC:1);elseif(is_object($captchaFoxBridgeResult)&&is_callable([$captchaFoxBridgeResult,'fetch_assoc']))$captchaFoxBridgeRow=$captchaFoxBridgeResult->fetch_assoc();}
$captchaFoxHooksAvailable=$captchaFoxNativeHooks||(is_array($captchaFoxBridgeRow)&&version_compare((string)($captchaFoxBridgeRow['version']??''),'1.2.0','>='));
$PRECHECK = array(
    'WBCE_VERSION' => array('VERSION' => '1.6.8', 'OPERATOR' => '>='),
    'PHP_VERSION' => array('VERSION' => '8.2.0', 'OPERATOR' => '>='),
    'WB_ADDONS' => array('captcha_control' => array('VERSION' => '3.1.33', 'OPERATOR' => '>=')),
    'CUSTOM_CHECKS'=>array($CAPTCHA_PRECHECK_LABEL=>array('REQUIRED'=>$CAPTCHA_PROVIDER['hooks_required'],'ACTUAL'=>$captchaFoxHooksAvailable?$CAPTCHA_PROVIDER['available']:$CAPTCHA_PROVIDER['unavailable'],'STATUS'=>$captchaFoxHooksAvailable)),
);
