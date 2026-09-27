<?php
/**
 * WBCE CMS
 * Way Better Content Editing.
 * Visit https://wbce.org to learn more and to join the community.
 *
 * @copyright Ryan Djurovich (2004-2009)
 * @copyright WebsiteBaker Org. e.V. (2009-2015)
 * @copyright WBCE Project (2015-)
 * @license GNU GPL2 (or any later version)
 */

$module_directory = 'jsadmin';
$module_uuid = 'e8629d9a-c4e6-4269-8789-f9144c7be6e5';
$metadataLanguage=defined('LANGUAGE')?strtoupper((string)LANGUAGE):'EN';$metadataFile=__DIR__.'/languages/metadata/'.$metadataLanguage.'.php';if(!is_readable($metadataFile))$metadataFile=__DIR__.'/languages/metadata/EN.php';if(is_readable($metadataFile))require $metadataFile;
$module_name = 'jsAdmin';
$module_function = 'tool';
$module_version='1.4.15';
$module_platform = '1.7.0';
$module_requires_php = '8.2.0';
$module_requires_any = 'WBCE>=1.6.8';
$module_author = 'Stepan Riha, Swen Uth';
$module_license = 'BSD License';
$module_description = 'Steuert die JavaScript-Funktionen für Seitenbaum und Abschnittsverwaltung im WBCE-Backend.';
$module_icon = 'fa fa-sitemap';
$module_level = 'core';

/**
 * Version history
 *
 * 1.4.15 - Asynchronous settings requests restore the FTAN when output filters clear hidden values.
 *
 * 1.4.4 - fixes for MySQL-Strict (Bernd)
 *
 * 1.4.3
 *        - Add module_level core status
 *        - Update module_platform
 *
 * 1.4.2
 *        - Add Admintool Icon
 *
 * 1.4.1
 *        - Making use of Insert class
 *
 **/
