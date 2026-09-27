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

$MOD_MENU_LINK = array();
require __DIR__.'/languages/EN.php';
$menuLinkLanguage = defined('LANGUAGE') ? __DIR__.'/languages/'.strtoupper((string) LANGUAGE).'.php' : '';
if ($menuLinkLanguage !== '' && is_file($menuLinkLanguage)) { require $menuLinkLanguage; }
$module_directory = 'menu_link';
$module_uuid = '61fa6b32-5778-4897-ab6e-db4724a6a3d8';
$module_name = isset($MOD_MENU_LINK['MODULE_NAME']) ? $MOD_MENU_LINK['MODULE_NAME'] : (isset($module_name) ? $module_name : 'Menu Link');
$module_function = 'page';
$module_version = '2.10.1';
$module_platform = '1.6.8';
$module_requires_php = '8.2.0';
$module_author = 'Ryan Djurovich, thorn, Christian M. Stefan';
$module_license = 'GNU General Public License';
$module_description = isset($MOD_MENU_LINK['MODULE_DESCRIPTION']) ? $MOD_MENU_LINK['MODULE_DESCRIPTION'] : (isset($module_description) ? $module_description : 'This module allows you to insert a link into the menu.');
$module_icon = 'fa fa-sitemap';
$module_level = 'core';

/**
 * Version history
 * 
 * 2.9.8 - cs fixed files
 *
 * 2.9.7 - add redirection type "200"
 * 
 * 2.9.6 - MYSQL_ASSOC -> MYSQLI_ASSOC
 *
 * 2.9.5 - Add module_level core status
 *       - Update module_platform
 *
 * 2.9.4 - Add module_name translation
 *
 **/
