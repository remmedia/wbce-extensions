<?php
defined('WB_PATH') or die('No direct access');
$CAPTCHA_PROVIDER=array();require __DIR__.'/languages/EN.php';$captchaRecaptchaLanguage=defined('LANGUAGE')?preg_replace('/[^A-Z]/','',strtoupper((string)LANGUAGE)):'EN';if($captchaRecaptchaLanguage!=='EN'&&is_file(__DIR__.'/languages/'.$captchaRecaptchaLanguage.'.php')){$captchaRecaptchaEnglish=$CAPTCHA_PROVIDER;require __DIR__.'/languages/'.$captchaRecaptchaLanguage.'.php';$CAPTCHA_PROVIDER=array_merge($captchaRecaptchaEnglish,$CAPTCHA_PROVIDER);}
$captchaRecaptchaUpgradeError=Settings::Set('captcha_recaptcha_installed','1');if($captchaRecaptchaUpgradeError)throw new RuntimeException((string)$captchaRecaptchaUpgradeError);
require_once __DIR__.'/Registration.php';if(!captcha_recaptcha_register_addon($database))throw new RuntimeException($CAPTCHA_PROVIDER['registration_failed']);
