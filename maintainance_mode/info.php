<?php
/**
 * @category        modules
 * @package         Maintenance Mode
 * @author          WBCE Project
 * @copyright       Norbert Heimsath
 * @license         WTFPL
 */

$module_directory = 'maintainance_mode';
$module_uuid = 'dc46fcc5-dd9a-4ec3-bfa5-018cbcae5c5e';
$metadataLanguage=defined('LANGUAGE')?strtoupper((string)LANGUAGE):'EN';$metadataFile=__DIR__.'/languages/metadata/'.$metadataLanguage.'.php';if(!is_readable($metadataFile))$metadataFile=__DIR__.'/languages/metadata/EN.php';if(is_readable($metadataFile))require $metadataFile;
$module_name = 'Wartungsmodus';
$module_function = 'tool';
$module_version='1.1.17';
$module_platform = '1.7.0';
$module_requires_php = '8.2.0';
$module_requires_any = 'WBCE>=1.6.8';
$module_author = 'Norbert Heimsath(heimsath.org)';
$module_license	= 'WTFPL';
$module_description = 'Aktiviert oder deaktiviert den Wartungsmodus der Website über einen Schiebeschalter.';
$module_icon = 'fa fa-wrench';
$module_level = 'core';

/**
 * Version history
 *
 * 1.1.4  - (florian) correct spelling (kept wrong directory name for compatibility reasons)
 *
 * 1.1.3
 *        - Add module_level core status
 *        - Update module_platform 
 *
 * 1.1.2
 *        - Add module_name translation
 *
 **/
