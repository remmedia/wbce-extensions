<?php
defined('WB_PATH') or die('No direct access');
$CAPTCHA_PROVIDER = array();
require __DIR__.'/languages/EN.php';
$captchaAltchaLanguage = defined('LANGUAGE') ? preg_replace('/[^A-Z]/', '', strtoupper((string)LANGUAGE)) : 'EN';
if ($captchaAltchaLanguage !== 'EN' && is_file(__DIR__.'/languages/'.$captchaAltchaLanguage.'.php')) {
    $captchaAltchaEnglish = $CAPTCHA_PROVIDER;
    require __DIR__.'/languages/'.$captchaAltchaLanguage.'.php';
    $CAPTCHA_PROVIDER = array_merge($captchaAltchaEnglish, $CAPTCHA_PROVIDER);
}
$captchaAltchaUpgradeError = Settings::Set('captcha_altcha_installed','1');
if ($captchaAltchaUpgradeError) throw new RuntimeException((string)$captchaAltchaUpgradeError);
require_once __DIR__.'/Registration.php';
if (!captcha_altcha_register_addon($database)) throw new RuntimeException($CAPTCHA_PROVIDER['registration_failed']);
