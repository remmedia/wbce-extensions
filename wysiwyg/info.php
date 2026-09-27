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

$MOD_WYSIWYG = array();
require __DIR__.'/languages/EN.php';
$wysiwygLanguage = defined('LANGUAGE') ? __DIR__.'/languages/'.strtoupper((string) LANGUAGE).'.php' : '';
if ($wysiwygLanguage !== '' && is_file($wysiwygLanguage)) { require $wysiwygLanguage; }
$module_directory = 'wysiwyg';
$module_uuid = '8e850004-4ebb-42b9-a90f-b70713b119e7';
$module_name = isset($MOD_WYSIWYG['MODULE_NAME']) ? $MOD_WYSIWYG['MODULE_NAME'] : 'WYSIWYG';
$module_function = 'page';
$module_version = '2.11.1';
$module_platform = '1.6.8';
$module_requires_php = '8.2.0';
$module_author = 'Ryan Djurovich';
$module_license = 'GNU General Public License';
$module_description = isset($MOD_WYSIWYG['MODULE_DESCRIPTION']) ? $MOD_WYSIWYG['MODULE_DESCRIPTION'] : $module_description;
$module_home = 'https://www.wbce.org';
