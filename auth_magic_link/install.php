<?php
defined('WB_PATH') or die('No direct access');
$settings = Settings::get('auth_magic_link_settings', []);
if (!is_array($settings)) $settings = [];
$settings += ['configured'=>false, 'enabled'=>false, 'scope'=>'disabled'];
$error = Settings::set('auth_magic_link_settings', $settings);
if ($error) throw new RuntimeException((string)$error);
$policy = WbceAuthenticationProviderManager::policy();
if (!isset($policy['providers']['auth_magic_link'])) {
    $policy['providers']['auth_magic_link'] = ['enabled'=>false, 'scope'=>'disabled'];
    WbceAuthenticationProviderManager::setPolicy($policy);
}
