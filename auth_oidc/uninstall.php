<?php
defined('WB_PATH') or die('No direct access');
Settings::del('auth_oidc_settings');
$policy = WbceAuthenticationProviderManager::policy();
unset($policy['providers']['auth_oidc']);
WbceAuthenticationProviderManager::setPolicy($policy);
