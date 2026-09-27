<?php
defined('WB_PATH') or die('No direct access');
$CAPTCHA_PROVIDER=array();require __DIR__.'/languages/EN.php';$captchaPhpLanguage=defined('LANGUAGE')?preg_replace('/[^A-Z]/','',strtoupper((string)LANGUAGE)):'EN';if($captchaPhpLanguage!=='EN'&&is_file(__DIR__.'/languages/'.$captchaPhpLanguage.'.php')){$captchaPhpEnglish=$CAPTCHA_PROVIDER;require __DIR__.'/languages/'.$captchaPhpLanguage.'.php';$CAPTCHA_PROVIDER=array_merge($captchaPhpEnglish,$CAPTCHA_PROVIDER);}
$captchaPhpUpgradeError=Settings::Set('captcha_phpcapcha_installed','1');if($captchaPhpUpgradeError)throw new RuntimeException((string)$captchaPhpUpgradeError);
require_once __DIR__.'/Registration.php';if(!captcha_phpcapcha_register_addon($database))throw new RuntimeException($CAPTCHA_PROVIDER['registration_failed']);
