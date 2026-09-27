<?php
defined('WB_PATH') or die('No direct access');
require_once __DIR__.'/Language.php';
if(!is_object($database)||!is_callable(array($database,'query'))){
    throw new RuntimeException(wbce_two_factor_t('database_unavailable'));
}
require_once __DIR__.'/Settings.php';
WbceTwoFactorSettings::install($database);
if (WbceTwoFactorSettings::get($database, 'selected_provider', '__missing__') === '__missing__') {
    // The first provider installed after the core selects itself. This must be
    // empty when the core is installed as a dependency of another provider.
    WbceTwoFactorSettings::set($database, 'selected_provider', '');
}
if (WbceTwoFactorSettings::get($database, 'enabled', '__missing__') === '__missing__') {
    WbceTwoFactorSettings::set($database, 'enabled', '0');
}
