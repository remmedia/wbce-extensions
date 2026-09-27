<?php
defined('WB_PATH') or die('No direct access');
if(!is_file(WB_PATH.'/modules/two_factor/Language.php')||!is_file(WB_PATH.'/modules/two_factor/Registry.php'))return;
require_once WB_PATH.'/modules/two_factor/Language.php';
if(is_file(WB_PATH.'/modules/two_factor/Compatibility.php'))require_once WB_PATH.'/modules/two_factor/Compatibility.php';
if(is_file(WB_PATH.'/modules/two_factor/Registry.php')){
    require_once WB_PATH.'/modules/two_factor/Registry.php';
    WbceTwoFactorRegistry::register('totp',array(
        'name'=>wbce_two_factor_t('provider_name',array(),'two_factor_totp'),
        'description'=>wbce_two_factor_t('provider_description',array(),'two_factor_totp'),
        'icon'=>'fa-mobile',
        'profile_url'=>WB_URL.'/modules/two_factor_totp/profile.php',
        'configured'=>static function($userId) use ($database){require_once __DIR__.'/Service.php';return(new WbceTotpService($database))->isEnabled($userId);},
        'disable_challenge'=>static function($userId){return array('type'=>'code');},
        'verify_disable'=>static function($userId,$value) use ($database){require_once __DIR__.'/Service.php';try{return(new WbceTotpService($database))->verify($userId,$value);}catch(Throwable $ignored){return false;}},
        'usage'=>static function() use ($database){
            require_once __DIR__.'/Service.php';
            return (new WbceTotpService($database))->countEnabled();
        },
    ));
}

// Compatibility boundary: stock WBCE 1.6.8/1.7 may load initialize.php but
// does not expose the authentication hooks until the bridge is installed.
if (!function_exists('wbce_add_filter') || !class_exists('WbceAuthFactorManager')) {
    return;
}

require_once __DIR__ . '/Provider.php';
WbceAuthFactorManager::register(new WbceTotpProvider($database));
if(class_exists('WbceTwoFactorCompatibility'))WbceTwoFactorCompatibility::enforce();
