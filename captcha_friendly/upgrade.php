<?php
defined('WB_PATH') or die('No direct access');
$CAPTCHA_PROVIDER=array();require __DIR__.'/languages/EN.php';$captchaFriendlyLanguage=defined('LANGUAGE')?preg_replace('/[^A-Z]/','',strtoupper((string)LANGUAGE)):'EN';if($captchaFriendlyLanguage!=='EN'&&is_file(__DIR__.'/languages/'.$captchaFriendlyLanguage.'.php')){$captchaFriendlyEnglish=$CAPTCHA_PROVIDER;require __DIR__.'/languages/'.$captchaFriendlyLanguage.'.php';$CAPTCHA_PROVIDER=array_merge($captchaFriendlyEnglish,$CAPTCHA_PROVIDER);}
$captchaFriendlyUpgradeError=Settings::Set('captcha_friendly_installed','1');if($captchaFriendlyUpgradeError)throw new RuntimeException((string)$captchaFriendlyUpgradeError);
require_once __DIR__.'/Registration.php';if(!captcha_friendly_register_addon($database))throw new RuntimeException($CAPTCHA_PROVIDER['registration_failed']);
