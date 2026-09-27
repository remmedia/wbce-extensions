<?php
defined('WB_PATH') or die('Access denied');
require_once __DIR__.'/Registry.php';
if(!isset($database)||!is_object($database)||!method_exists($database,'query'))return;
if(WbceApiRegistry::count($database)>0)throw new RuntimeException(wbce_api_t('in_use'));
$database->query('DROP TABLE IF EXISTS `'.TABLE_PREFIX.'mod_api_routes`');
