<?php
/**
 * @category        modules
 * @package         maintainance_mode
 * @author          WBCE Project
 * @copyright       Norbert Heimsath
 * @license			WTFPL
 */

/*
Info for module(tool) builders.
Already included here :
config.php
framework/initialize.php
framework/class.wb.php
framework/Admin.php
framework/functions.php

Admin class is initialized($admin) and header printed.

Additional vars for this tool: 
$modulePath     Path to this module directory
$languagePath   Path to language files of this module
$returnToTools  Url to return to generic tools page
$returnUrl      Url for return link after saving AND for sending the form!
$doSave         Set true if form is send
$saveSettings   Set true if there are actual settings send
$saveDefault    Set true if default button was pressed
$toolDir        Plain tool directory name like "maintainance_mode"
$toolName       The name of the tool eg "Maintainance Mode"

For language vars please take a look in the language files.
Language files no longer need manual loading.

All other vars usually abailable in Admin pages schould be available here too.
Maybe you need to import them via global.

backend.js and backend.css are automatically loaded, 
manual loading is no longer required.
*/

//no direct file access
if(count(get_included_files())==1){header("Location: ../index.php",TRUE,301);exit;}
$moduleUrl=WB_URL.'/modules/'.basename(__DIR__);
// Older WBCE admin controllers do not always register backend.css/backend.js
// automatically for tools. Load the interaction assets explicitly so the
// switch always persists its state asynchronously.
echo '<link rel="stylesheet" href="'.htmlspecialchars($moduleUrl.'/backend.css?v=1.1.16',ENT_QUOTES,'UTF-8').'">';
$selectedMaintenanceLanguage=isset($MOD_MAINTAINANCE)&&is_array($MOD_MAINTAINANCE)?$MOD_MAINTAINANCE:array();require __DIR__.'/languages/EN.php';$MOD_MAINTAINANCE=array_merge($MOD_MAINTAINANCE,$selectedMaintenanceLanguage);
$maintenanceSetting = Settings::Get('wb_maintainance_mode');
$maintMode = (string)$maintenanceSetting === '1' || $maintenanceSetting === true || $maintenanceSetting === 'btrueb';
include __DIR__.'/templates/maintainance.tpl.php';
echo '<script src="'.htmlspecialchars($moduleUrl.'/backend.js?v=1.1.16',ENT_QUOTES,'UTF-8').'" defer></script>';
 
