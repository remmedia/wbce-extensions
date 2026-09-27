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

$MOD_WRAPPER = array();
require __DIR__.'/languages/EN.php';
$wrapperLanguage = defined('LANGUAGE') ? __DIR__.'/languages/'.strtoupper((string) LANGUAGE).'.php' : '';
if ($wrapperLanguage !== '' && is_file($wrapperLanguage)) { require $wrapperLanguage; }
$module_directory = 'wrapper';
$module_uuid = 'cc0e3d55-1e9a-422c-87b9-b106d86adc8d';
$module_name = isset($MOD_WRAPPER['MODULE_NAME']) ? $MOD_WRAPPER['MODULE_NAME'] : 'Wrapper';
$module_function = 'page';
$module_version = '2.9.1';
$module_platform = '1.6.8';
$module_requires_php = '8.2.0';
$module_author = 'Ryan Djurovich';
$module_license = 'GNU General Public License';
$module_description = isset($MOD_WRAPPER['MODULE_DESCRIPTION']) ? $MOD_WRAPPER['MODULE_DESCRIPTION'] : $module_description;
