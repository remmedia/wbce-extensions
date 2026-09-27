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

$module_directory = 'ckeditor';
$module_uuid = 'd27042d3-e2d2-4f89-a20c-35753638e21a';
$module_name = 'CKEditor 5';
$module_description = 'Moderner visueller HTML-Editor mit WBCE-Medienverwaltung, internen Seitenlinks, Droplets, Einbettungen, Symbolen und direkter Speicherfunktion.';
$ckeditorMetadataLanguage = defined('LANGUAGE') ? strtoupper(substr((string) LANGUAGE, 0, 2)) : 'EN';
$ckeditorMetadataFile = __DIR__.'/languages/'.preg_replace('/[^A-Z]/', '', $ckeditorMetadataLanguage).'.php';
if (!is_file($ckeditorMetadataFile)) $ckeditorMetadataFile = __DIR__.'/languages/EN.php';
$CKEDITOR5_TEXT = array();
require $ckeditorMetadataFile;
$module_name = $CKEDITOR5_TEXT['MODULE_NAME'];
$module_function = 'WYSIWYG,snippet,initialize';
$module_version = '4.22.1';
$module_platform = '1.6.8';
$module_requires_php = '8.2.0';
$module_author = 'CKSource, WBCE Community';
$module_license = 'GPL-2.0-or-later';
$module_description = $CKEDITOR5_TEXT['MODULE_DESCRIPTION'];
$module_home = 'https://www.wbce.org';
$module_icon = 'fa fa-pencil-square-o';
