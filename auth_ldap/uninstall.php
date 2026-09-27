<?php
defined('WB_PATH') or die('No direct access');
Settings::del('auth_ldap_settings');
$policy = WbceAuthenticationProviderManager::policy();
unset($policy['providers']['auth_ldap']);
WbceAuthenticationProviderManager::setPolicy($policy);
