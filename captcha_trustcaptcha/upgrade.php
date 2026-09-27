<?php
defined('WB_PATH') or die('No direct access');
$CAPTCHA_PROVIDER=array();require __DIR__.'/languages/EN.php';$captchaTrustLanguage=defined('LANGUAGE')?preg_replace('/[^A-Z]/','',strtoupper((string)LANGUAGE)):'EN';if($captchaTrustLanguage!=='EN'&&is_file(__DIR__.'/languages/'.$captchaTrustLanguage.'.php')){$captchaTrustEnglish=$CAPTCHA_PROVIDER;require __DIR__.'/languages/'.$captchaTrustLanguage.'.php';$CAPTCHA_PROVIDER=array_merge($captchaTrustEnglish,$CAPTCHA_PROVIDER);}
$captchaTrustUpgradeError=Settings::Set('captcha_trustcaptcha_installed','1');if($captchaTrustUpgradeError)throw new RuntimeException((string)$captchaTrustUpgradeError);
require_once __DIR__.'/Registration.php';if(!captcha_trustcaptcha_register_addon($database))throw new RuntimeException($CAPTCHA_PROVIDER['registration_failed']);
