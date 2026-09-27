<?php
defined('WB_PATH') or die('No direct access');
$CAPTCHA_PROVIDER=array();require __DIR__.'/languages/EN.php';$captchaFoxLanguage=defined('LANGUAGE')?preg_replace('/[^A-Z]/','',strtoupper((string)LANGUAGE)):'EN';if($captchaFoxLanguage!=='EN'&&is_file(__DIR__.'/languages/'.$captchaFoxLanguage.'.php')){$captchaFoxEnglish=$CAPTCHA_PROVIDER;require __DIR__.'/languages/'.$captchaFoxLanguage.'.php';$CAPTCHA_PROVIDER=array_merge($captchaFoxEnglish,$CAPTCHA_PROVIDER);}
$captchaFoxUpgradeError=Settings::Set('captcha_captchafox_installed','1');if($captchaFoxUpgradeError)throw new RuntimeException((string)$captchaFoxUpgradeError);
require_once __DIR__.'/Registration.php';if(!captcha_captchafox_register_addon($database))throw new RuntimeException($CAPTCHA_PROVIDER['registration_failed']);
