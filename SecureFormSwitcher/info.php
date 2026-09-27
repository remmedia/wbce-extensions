<?php
/**
 * @category        modules
 * @package         More Security Settings(SecureFormSwitcher)
 * @author          WBCE Project
 * @copyright       Norbert Heimsath
 * @license         WTFPL
 */

$module_directory = 'SecureFormSwitcher';
$module_uuid = 'f319adf5-4d44-4c80-8674-2caa8a74f99f';
$metadataLanguage = defined('LANGUAGE') ? strtoupper((string) LANGUAGE) : 'EN';
$metadataFile = __DIR__.'/languages/'.$metadataLanguage.'.php';
if(!is_readable($metadataFile)) $metadataFile=__DIR__.'/languages/EN.php';
$SFS = array();
require $metadataFile;
$module_name = 'Secure Form Switcher';
$module_function = 'tool';
$module_version='1.3.14';
$module_platform = '1.7.0';
$module_requires_php = '8.2.0';
$module_requires_any = 'WBCE>=1.6.8';
$module_author = 'Complete rewrite of Secure Form Switcher by  Norbert Heimsath(heimsath.org)';
$module_license = 'GPLv2 or any later';
$module_description = 'Konfiguriert sichere Sitzungen, Formular-Token, Zeitlimits und zusätzliche Schutzfunktionen.';
$module_icon = 'fa fa-lock';
$module_level = 'core';

/**
 * Version history
 *
 * 1.3.3 - cs fixed files
 *
 * 1.3.2 - Add module_level core status
 *
 * 1.3.1 - Add Add Admintool Icon
 *
 **/
