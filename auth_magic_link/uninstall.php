<?php
defined('WB_PATH') or die('No direct access');
Settings::del('auth_magic_link_settings');
$policy = WbceAuthenticationProviderManager::policy();
unset($policy['providers']['auth_magic_link']);
WbceAuthenticationProviderManager::setPolicy($policy);
