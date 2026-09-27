<?php

defined('WB_PATH') or exit;

require_once __DIR__ . '/schema.php';
if (!isset($database) || !toolAccountSettingsEnsureSchema($database)) {
    $metadataLanguage = defined('LANGUAGE') ? strtoupper(substr((string)LANGUAGE, 0, 2)) : 'EN';
    $metadataFile = __DIR__ . '/languages/metadata/' . preg_replace('/[^A-Z]/', '', $metadataLanguage) . '.php';
    if (!is_file($metadataFile)) $metadataFile = __DIR__ . '/languages/metadata/EN.php';
    $metadata = (array)require $metadataFile;
    throw new RuntimeException((string)$metadata['schema_error']);
}
