<?php
/**
 * WBCE CMS AdminTool: tool_account_settings
 * 
 * @platform    WBCE CMS 1.4.0
 * @package     modules/tool_account_settings
 * @author      Christian M. Stefan <stefek@designthings.de>
 * @copyright   Christian M. Stefan
 * @license     see LICENSE.md of this package
 */
 
// prevent this file from being accessed directly
if (!defined('WB_PATH')) { header('Location: ../index.php'); exit; }

require_once __DIR__ . '/schema.php';
if (!isset($database) || !toolAccountSettingsEnsureSchema($database)) {
    $metadataLanguage = defined('LANGUAGE') ? strtoupper(substr((string)LANGUAGE, 0, 2)) : 'EN';
    $metadataFile = __DIR__ . '/languages/metadata/' . preg_replace('/[^A-Z]/', '', $metadataLanguage) . '.php';
    if (!is_file($metadataFile)) $metadataFile = __DIR__ . '/languages/metadata/EN.php';
    $metadata = (array)require $metadataFile;
    throw new RuntimeException((string)$metadata['schema_error']);
}
