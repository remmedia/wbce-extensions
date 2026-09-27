<?php
defined('WB_PATH') or die('No direct access');
$CAPTCHA_PROVIDER=array(); require __DIR__.'/languages/EN.php';
$captchaCapLanguage=defined('LANGUAGE')?preg_replace('/[^A-Z]/','',strtoupper((string)LANGUAGE)):'EN';
if($captchaCapLanguage!=='EN'&&is_file(__DIR__.'/languages/'.$captchaCapLanguage.'.php')){$captchaCapEnglish=$CAPTCHA_PROVIDER;require __DIR__.'/languages/'.$captchaCapLanguage.'.php';$CAPTCHA_PROVIDER=array_merge($captchaCapEnglish,$CAPTCHA_PROVIDER);}
$captchaCapUpgradeError=Settings::Set('captcha_cap_installed', '1');
if($captchaCapUpgradeError)throw new RuntimeException((string)$captchaCapUpgradeError);
require_once __DIR__.'/Registration.php'; if(!captcha_cap_register_addon($database))throw new RuntimeException($CAPTCHA_PROVIDER['registration_failed']);
