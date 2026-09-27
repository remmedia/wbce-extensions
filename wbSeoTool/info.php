<?php
/**
 * WebsiteBaker CMS AdminTool: wbSeoTool
 *
 * This file defines the obligatory variables required for WebsiteBaker CMS
 *
 *
 * @platform    CMS WebsiteBaker 2.8.x
 * @package     wbSeoTool
 * @author      Christian M. Stefan (Stefek)
 * @copyright   Christian M. Stefan
 * @license     http://www.gnu.org/licenses/gpl-2.0.html
 */

$TOOL_TEXT = array();
require __DIR__.'/languages/EN.php';
$seoLanguage = defined('LANGUAGE') ? __DIR__.'/languages/'.strtoupper((string) LANGUAGE).'.php' : '';
if ($seoLanguage !== '' && is_file($seoLanguage)) { require $seoLanguage; }
$module_directory   = 'wbSeoTool';
$module_uuid = '3f3fbe43-9136-47a9-895a-2ad4f22a7dae';
$module_name        = 'SEO Tool';
$module_description = 'Manages page titles, descriptions, keywords and optional rewritten URLs in a clear page tree.';
$module_name        = isset($TOOL_TEXT['MODULE_NAME']) ? $TOOL_TEXT['MODULE_NAME'] : $module_name;
$module_function    = 'tool';
$module_version     = '0.8.5';
$module_status      = 'Stable';
$module_platform    = '1.6.8';
$module_requires_php = '8.2.0';
$module_author      = 'Christian M. Stefan <stefek@designthings.de>';
$module_license     = 'GNU General Public License v.2';
$module_description = isset($TOOL_TEXT['MODULE_DESCRIPTION']) ? $TOOL_TEXT['MODULE_DESCRIPTION'] : $module_description;
$module_icon        = 'fa fa-tasks';

/**
 * Version history
 *
 * 0.7.1 - colinax
 *       - fix versioning bug
 *
 * 0.7.0 - Bianka Martinovic ("WebBird")
 *       - fix issues with changed twig version and PHP 8
 *
 * 0.6.2 - Bernd
 *       - MYSQL_ASSOC -> MYSQLI_ASSOC
 *
 * 0.6.1 - colinax
 *       - Add Admintool Icon
 *
 **/
