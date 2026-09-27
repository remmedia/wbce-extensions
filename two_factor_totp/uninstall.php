<?php
defined('WB_PATH') or die('No direct access');
if (is_file(WB_PATH.'/modules/two_factor/Registry.php')) {
    require_once WB_PATH.'/modules/two_factor/Registry.php';
    WbceTwoFactorRegistry::assertCanUninstall('totp',$database);
}
if (is_file(WB_PATH.'/modules/two_factor/Settings.php')) { require_once WB_PATH.'/modules/two_factor/Settings.php'; WbceTwoFactorSettings::removeProviderData($database,'totp'); }
$database->query('DROP TABLE IF EXISTS `' . TABLE_PREFIX . 'mod_two_factor_totp_recovery`');
$database->query('DROP TABLE IF EXISTS `' . TABLE_PREFIX . 'mod_two_factor_totp_attempt`');
$database->query('DROP TABLE IF EXISTS `' . TABLE_PREFIX . 'mod_two_factor_totp_settings`');
$database->query('DROP TABLE IF EXISTS `' . TABLE_PREFIX . 'mod_two_factor_totp`');
$keyFile = __DIR__ . '/key.php';
if (file_exists($keyFile)) {
    @unlink($keyFile);
}
