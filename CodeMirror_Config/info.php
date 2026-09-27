<?php
/**
 *
 * @category        admintool / initialize 
 * @package         CodeMirror_Config
 * @author          Christian M. Stefan
 * @license         http://www.gnu.org/licenses/gpl.html
 * @platform        WBCE 1.5.5
 *
 */

// Must include code to stop this file from being accessed directly
defined('WB_PATH') or die("This file can't be accessed directly!");

$module_directory   = 'CodeMirror_Config';
$module_uuid = '5485e64c-90d0-433c-af7b-94a2cb0238e2';
$module_name        = 'CodeMirror Configurator';
$module_description = 'Configures the CodeMirror editor theme, font, font size and backend integration.';
$module_function    = 'tool, initialize';
$module_version='0.1.11';
$module_platform    = '1.7.0';
$module_requires_php = '8.2.0';
$module_author      = 'Christian M. Stefan (Stefek)';
                      // Some of the CodeMirror implementation mechanism have  
                      // been inspired by Martin Hecht's (mrbaseman) work
                      // in the Code2 module. Thank you.
$module_license	    = 'GNU General Public License';
                      // Please see codemirror/LICENSE to view the
                      // license of the CodeMirror script
                      // More information can be found
                      // here: https://github.com/codemirror/codemirror5
$metadataLanguage = defined('LANGUAGE') ? strtoupper((string) LANGUAGE) : 'EN';
$metadataFile = __DIR__ . '/languages/metadata/' . $metadataLanguage . '.php';
if (!is_readable($metadataFile)) $metadataFile = __DIR__ . '/languages/metadata/EN.php';
if (is_readable($metadataFile)) require $metadataFile;
$module_name = isset($cmcMetadataName) ? $cmcMetadataName : $module_name;
$module_description = isset($cmcMetadataDescription) ? $cmcMetadataDescription : $module_description;
$module_icon        = 'fa fa-code';
