<?php
/**
 * Updater
 *
 * Intelligenter Update-Helfer für WBCE CMS
 *
 * @category    module
 * @package     updater
 * @author      WBCE Community
 * @copyright   2026 WBCE Community
 * @license     MIT License
 */

// prevent this file from being accessed directly
if (!defined('WB_PATH')) {
    exit('Direct access to this file is not allowed');
}

$module_directory     = 'updater';
$module_uuid = '0b13b6ee-a0af-4422-a2d7-317dc03ba766';
$module_name          = 'Updater';
$module_function      = 'tool';
$module_icon          = 'fa  fa-cloud-download';
$module_version='1.0.109';
$module_upload_endpoint='chunk_upload.php';
$module_platform      = '1.7.0';
$module_requires_php  = '8.2.0';
$module_author        = 'WBCE Community';
$module_license       = 'MIT License';
$module_description   = 'Prüft verfügbare WBCE-Versionen und deren PHP-Kompatibilität, lädt oder übernimmt Update-Pakete, validiert ZIP-Dateien und Prüfsummen und führt den Aktualisierungsvorgang optional im Wartungsmodus aus.';
$module_guid          = 'wbce-updater-2026';
