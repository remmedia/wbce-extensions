<?php
defined('WB_PATH') or die('No direct access');
$settings = Settings::get('auth_oidc_settings', []);
if (!is_array($settings)) $settings = [];
$settings += ['configured'=>false, 'enabled'=>false, 'scope'=>'disabled'];
$error = Settings::set('auth_oidc_settings', $settings);
if ($error) throw new RuntimeException((string)$error);
$policy = WbceAuthenticationProviderManager::policy();
if (!isset($policy['providers']['auth_oidc'])) {
    $policy['providers']['auth_oidc'] = ['enabled'=>false, 'scope'=>'disabled'];
    WbceAuthenticationProviderManager::setPolicy($policy);
}
