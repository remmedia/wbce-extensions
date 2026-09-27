<?php
defined('WB_PATH') or die('No direct access');
$CAPTCHA_PROVIDER=array();require __DIR__.'/languages/EN.php';$captchaIconLanguage=defined('LANGUAGE')?preg_replace('/[^A-Z]/','',strtoupper((string)LANGUAGE)):'EN';if($captchaIconLanguage!=='EN'&&is_file(__DIR__.'/languages/'.$captchaIconLanguage.'.php')){$captchaIconEnglish=$CAPTCHA_PROVIDER;require __DIR__.'/languages/'.$captchaIconLanguage.'.php';$CAPTCHA_PROVIDER=array_merge($captchaIconEnglish,$CAPTCHA_PROVIDER);}
$captchaIconErrors=array(Settings::Set('captcha_icon_timeout','600',false),Settings::Set('captcha_icon_installed','1'));foreach($captchaIconErrors as $captchaIconError)if($captchaIconError)throw new RuntimeException((string)$captchaIconError);
require_once __DIR__.'/Registration.php';if(!captcha_icon_register_addon($database))throw new RuntimeException($CAPTCHA_PROVIDER['registration_failed']);
