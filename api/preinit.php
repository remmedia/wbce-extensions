<?php
defined('WB_PATH') or die('Access denied');
require_once __DIR__.'/Registry.php';
if(function_exists('wbce_add_filter'))wbce_add_filter('addon.beforeUninstall',static function($allowed,$context=null){global $database;if(is_array($allowed)&&$context===null){$context=$allowed;$allowed=true;}if(is_array($context)&&($context['type']??'')==='module'&&($context['directory']??'')==='api'&&WbceApiRegistry::count($database)>0)return false;return $allowed;},1);
