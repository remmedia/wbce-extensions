<?php
/**
 * WBCE CMS AdminTool: tool_account_settings
 *
 * @platform    WBCE CMS 1.4.0 and higher
 * @package     modules/tool_account_settings
 * @author      Christian M. Stefan <stefek@designthings.de>
 * @copyright   Christian M. Stefan
 * @license     see LICENSE.md of this package
 */

// prevent this file from being accessed directly
if (!defined('WB_PATH')) { header('Location: ../index.php'); exit; }

$module_directory   = 'tool_account_settings';
$module_uuid = 'b742b931-c926-4922-89f0-5fe2061f3fb3';
$module_name        = 'Account- und Registrierungseinstellungen';
$module_function    = 'tool';
$module_version='0.7.22';
$module_platform    = '1.7.0';
$module_author      = 'Christian M. Stefan (Stefek)';
$module_icon        = 'fa fa-user-circle-o';
$module_level       = 'core';
$module_license     = 'GNU General Public License';
$module_description = 'Konfiguriert Benutzerkonten, Anmeldung, Registrierung, Freigaben und zugehörige E-Mail-Texte und bietet eine Übersicht aller Benutzerkonten.';
$module_requires_any = 'WBCE>=1.7.0|wbce_hook_bridge>=1.2.0';
$module_requires_php = '8.2.0';
