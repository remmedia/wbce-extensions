<?php
defined('WB_PATH') or die('No direct access');
if (!function_exists('wbce_add_filter') || !function_exists('wbce_add_action')) {
    $captchaAltchaBridge = WB_PATH . '/modules/wbce_hook_bridge/preinit.php';
    if (is_file($captchaAltchaBridge)) require_once $captchaAltchaBridge;
}
if (!function_exists('wbce_add_filter') || !function_exists('wbce_add_action')) { return; }
if (defined('WBCE_CAPTCHA_PROVIDER_ALTCHA_REGISTERED')) { return; }
define('WBCE_CAPTCHA_PROVIDER_ALTCHA_REGISTERED', true);
if((string)Settings::Get('captcha_altcha_installed','')!=='1')return; require_once WB_PATH.'/modules/captcha_control/ProviderUi.php'; require_once __DIR__.'/Settings.php'; require_once __DIR__.'/Provider.php';
$captchaAltchaText=WbceCaptchaProviderUi::language(__DIR__,array());
wbce_add_filter('captcha.providers', function($providers)use($captchaAltchaText){ $providers['altcha']=array('name'=>$captchaAltchaText['name'],'description'=>$captchaAltchaText['description'],'module'=>'captcha_altcha','render'=>array('WbceAltchaCaptchaProvider','render'),'verify'=>array('WbceAltchaCaptchaProvider','verify')); return $providers; });
wbce_add_filter('captcha.settings.sections', function($sections,$selected){ if($selected==='altcha') $sections[]=WbceAltchaProviderSettings::form(); return $sections; });
wbce_add_action('captcha.settings.save', function($input,$selected){ if($selected==='altcha') WbceAltchaProviderSettings::save($input); });
