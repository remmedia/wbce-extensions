<?php
defined('WB_PATH') or die('No direct access');
Settings::del('auth_qr_login_settings');
$policy = WbceAuthenticationProviderManager::policy();
unset($policy['providers']['auth_qr_login']);
WbceAuthenticationProviderManager::setPolicy($policy);
