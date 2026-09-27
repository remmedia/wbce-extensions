<?php
defined('WB_PATH') or die('No direct access');
Settings::del('auth_radius_settings');
$policy = WbceAuthenticationProviderManager::policy();
unset($policy['providers']['auth_radius']);
WbceAuthenticationProviderManager::setPolicy($policy);
