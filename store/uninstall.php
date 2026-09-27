<?php
defined('WB_PATH') or die('No direct access');
$database->query('DROP TABLE IF EXISTS `' . TABLE_PREFIX . 'mod_store_sources`');
$database->query('DROP TABLE IF EXISTS `' . TABLE_PREFIX . 'mod_store_origins`');
