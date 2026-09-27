<?php
defined('WB_PATH') or die('No direct access');
Settings::del('auth_imap_settings');
$policy = WbceAuthenticationProviderManager::policy();
unset($policy['providers']['auth_imap']);
WbceAuthenticationProviderManager::setPolicy($policy);
