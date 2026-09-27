<?php
defined('WB_PATH') or die('Access denied');
require_once __DIR__.'/Registry.php';WbceApiRegistry::install($database);
foreach(glob(WB_PATH.'/modules/*/api_register.php')?:array() as $registration)require $registration;
