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

$SHOW_MENU2 = array();
require __DIR__.'/languages/EN.php';
$showMenuLanguage = defined('LANGUAGE') ? __DIR__.'/languages/'.strtoupper((string) LANGUAGE).'.php' : '';
if ($showMenuLanguage !== '' && is_file($showMenuLanguage)) { require $showMenuLanguage; }
$module_directory = 'show_menu2';
$module_uuid = 'c59aceb4-3923-4340-a157-be685bde0f98';
$module_name = isset($SHOW_MENU2['MODULE_NAME']) ? $SHOW_MENU2['MODULE_NAME'] : 'show_menu2';
$module_function = 'snippet';
$module_version = '4.14.3';
$module_platform = '1.6.8';
$module_requires_php = '8.2.0';
$module_author = 'diverse, WBCE Dev Team';
$module_license = 'GNU General Public License v2';
$module_description = isset($SHOW_MENU2['MODULE_DESCRIPTION']) ? $SHOW_MENU2['MODULE_DESCRIPTION'] : $module_description;
$module_level = 'core';
