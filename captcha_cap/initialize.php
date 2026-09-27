<?php
defined('WB_PATH') or die('No direct access');
if (!function_exists('wbce_add_filter') || !function_exists('wbce_add_action')) {
    $captchaCapBridge = WB_PATH . '/modules/wbce_hook_bridge/preinit.php';
    if (is_file($captchaCapBridge)) require_once $captchaCapBridge;
}
if (!function_exists('wbce_add_filter') || !function_exists('wbce_add_action')) return;
if (defined('WBCE_CAPTCHA_PROVIDER_CAP_REGISTERED')) return;
define('WBCE_CAPTCHA_PROVIDER_CAP_REGISTERED', true);
if ((string) Settings::Get('captcha_cap_installed', '') !== '1') return;
require_once __DIR__ . '/Provider.php';
require_once WB_PATH . '/modules/captcha_control/ProviderUi.php';
$captchaCapText = WbceCaptchaProviderUi::language(__DIR__, array());
wbce_add_filter('captcha.providers', function ($providers) use ($captchaCapText) {
    $providers['cap'] = array('name'=>$captchaCapText['name'],'description'=>$captchaCapText['description'],'module'=>'captcha_cap','render'=>array('WbceCapCaptchaProvider','render'),'verify'=>array('WbceCapCaptchaProvider','verify')); return $providers;
});
wbce_add_filter('captcha.settings.sections', function ($sections, $selected) use ($captchaCapText) {
    if ($selected !== 'cap') return $sections;
    $content = WbceCaptchaProviderUi::row($captchaCapText['endpoint'], WbceCaptchaProviderUi::input('url','captcha_cap_endpoint',(string) Settings::Get('captcha_cap_endpoint',''),array('placeholder'=>'https://captcha.example.org')))
        . WbceCaptchaProviderUi::row($captchaCapText['site_key'], WbceCaptchaProviderUi::input('text','captcha_cap_site_key',(string) Settings::Get('captcha_cap_site_key',''),array('autocomplete'=>'off')))
        . WbceCaptchaProviderUi::row($captchaCapText['secret'], WbceCaptchaProviderUi::input('password','captcha_cap_secret','',array('autocomplete'=>'new-password','placeholder'=>$captchaCapText['keep_secret'])).WbceCaptchaProviderUi::storedSecret((string)Settings::Get('captcha_cap_secret','')!=='',$captchaCapText['stored']));
    $sections[] = WbceCaptchaProviderUi::fieldset($captchaCapText['configure'], $content); return $sections;
});
wbce_add_action('captcha.settings.save', function ($input, $selected) {
    if ($selected !== 'cap') return;
    $value = static function ($key) use ($input) { return isset($input[$key]) && is_scalar($input[$key]) ? trim((string)$input[$key]) : ''; };
    $errors = array(
        Settings::Set('captcha_cap_endpoint',WbceCapCaptchaProvider::normalizeEndpoint($value('captcha_cap_endpoint'))),
        Settings::Set('captcha_cap_site_key',$value('captcha_cap_site_key')),
    );
    if ($value('captcha_cap_secret')!=='') $errors[] = Settings::Set('captcha_cap_secret',$value('captcha_cap_secret'));
    foreach ($errors as $error) if ($error) throw new RuntimeException((string)$error);
});
