<?php
defined('WB_PATH') or die('No direct access');
if(is_file(WB_PATH.'/modules/two_factor/Registry.php')){require_once WB_PATH.'/modules/two_factor/Registry.php';WbceTwoFactorRegistry::assertCanUninstall('whatsapp',$database);}
if(is_file(WB_PATH.'/modules/two_factor/Settings.php')){require_once WB_PATH.'/modules/two_factor/Settings.php';WbceTwoFactorSettings::removeProviderData($database,'whatsapp');}
$database->query('DROP TABLE IF EXISTS `{TP}mod_two_factor_whatsapp_codes`');
$database->query('DROP TABLE IF EXISTS `{TP}mod_two_factor_whatsapp_users`');
$database->query('DROP TABLE IF EXISTS `{TP}mod_two_factor_whatsapp_settings`');
if(is_file(__DIR__.'/key.php'))@unlink(__DIR__.'/key.php');
