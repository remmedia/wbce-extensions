<?php
defined('WB_PATH') or die('No direct access');
require_once __DIR__.'/src/OpfFallback.php';
WbceAccessibilityToolsOpfFallback::unregister();
if(isset($database)&&is_object($database)&&method_exists($database,'query'))$database->query('DROP TABLE IF EXISTS `{TP}mod_accessibility_tools_settings`');
