<?php
defined('WB_PATH') or die('No direct access');
Settings::del('auth_webauthn_settings');
$policy = WbceAuthenticationProviderManager::policy();
unset($policy['providers']['auth_webauthn']);
WbceAuthenticationProviderManager::setPolicy($policy);
