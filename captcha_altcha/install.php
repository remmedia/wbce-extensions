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
try {
    $captchaAltchaSecret = bin2hex(random_bytes(32));
} catch (Throwable $error) {
    throw new RuntimeException($CAPTCHA_PROVIDER['secure_random_required'], 0, $error);
}
$captchaAltchaErrors = array();
$captchaAltchaErrors[] = Settings::Set('captcha_altcha_secret', $captchaAltchaSecret, false);
$captchaAltchaErrors[] = Settings::Set('captcha_altcha_complexity', '50000', false);
if ((string)Settings::Get('captcha_altcha', '') === '') {
    $captchaAltchaConfig = json_encode(array('auto'=>'off','delay'=>0,'hidefooter'=>false,'hidelogo'=>false,'color_brand'=>'','color_success'=>'','color_base'=>'','color_checkbox'=>'','color_text'=>'','border_radius'=>''), JSON_UNESCAPED_SLASHES);
    if (!is_string($captchaAltchaConfig)) throw new RuntimeException($CAPTCHA_PROVIDER['settings_encode_failed']);
    $captchaAltchaErrors[] = Settings::Set('captcha_altcha', $captchaAltchaConfig, false);
}
$captchaAltchaErrors[] = Settings::Set('captcha_altcha_installed', '1');
foreach ($captchaAltchaErrors as $captchaAltchaError) {
    if ($captchaAltchaError) throw new RuntimeException((string)$captchaAltchaError);
}
require_once __DIR__.'/Registration.php';
if (!captcha_altcha_register_addon($database)) throw new RuntimeException($CAPTCHA_PROVIDER['registration_failed']);
