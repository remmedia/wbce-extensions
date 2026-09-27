<?php
defined('WB_PATH') or die('No direct access');
if(is_file(WB_PATH.'/modules/two_factor/Registry.php')){require_once WB_PATH.'/modules/two_factor/Registry.php';WbceTwoFactorRegistry::assertCanUninstall('email',$database);}
if(is_file(WB_PATH.'/modules/two_factor/Settings.php')){require_once WB_PATH.'/modules/two_factor/Settings.php';WbceTwoFactorSettings::removeProviderData($database,'email');}
$database->query('DROP TABLE IF EXISTS `{TP}mod_two_factor_email_codes`');
$database->query('DROP TABLE IF EXISTS `{TP}mod_two_factor_email_users`');
$database->query('DROP TABLE IF EXISTS `{TP}mod_two_factor_email_settings`');
