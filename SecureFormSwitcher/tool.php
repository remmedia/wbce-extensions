<?php
/**
 * @category        modules
 * @package         Security Settings
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

// no direct file access
if (count(get_included_files())==1) {
    header("Location: ../index.php", true, 301);
}
$moduleUrl = WB_URL . '/modules/' . basename(__DIR__);
$sfsLanguage = defined('LANGUAGE') ? strtoupper((string)LANGUAGE) : 'EN';
require __DIR__.'/languages/EN.php';
$sfsFallback = $SFS;
if ($sfsLanguage !== 'EN' && preg_match('/^[A-Z]{2}$/D', $sfsLanguage) && is_readable(__DIR__.'/languages/'.$sfsLanguage.'.php')) require __DIR__.'/languages/'.$sfsLanguage.'.php';
$SFS = array_merge($sfsFallback, isset($SFS) && is_array($SFS) ? $SFS : array());

$useFP = (bool)Settings::Get('wb_secform_usefp');
$ipOctets = (string)Settings::Get('fingerprint_with_ip_octets');
$tokenName = (string)Settings::Get('wb_secform_tokenname');
$timeout = (string)Settings::Get('wb_secform_timeout');
$secret = (string)Settings::Get('wb_secform_secret');
$secretTime = (string)Settings::Get('wb_secform_secrettime');
include __DIR__.'/templates/sfs.tpl.php';
