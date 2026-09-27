<?php
defined('WB_PATH') or die('No direct access');
Settings::del('auth_saml_settings');
$policy = WbceAuthenticationProviderManager::policy();
unset($policy['providers']['auth_saml']);
WbceAuthenticationProviderManager::setPolicy($policy);
