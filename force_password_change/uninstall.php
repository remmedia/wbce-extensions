<?php
defined('WB_PATH') or die('No direct access');
global $database;
require_once __DIR__ . '/Compatibility.php';
WbceForcePasswordChangeCompatibility::execute($database, 'DROP TABLE IF EXISTS `' . TABLE_PREFIX . 'mod_force_password_change`');
