<?php
/**
 * Updater - Upgrade Script
 *
 * Wird beim Upgrade des Moduls ausgeführt
 * Kann zukünftig für Migrations-Aufgaben verwendet werden
 *
 * @category    module
 * @package     updater
 * @version     1.0.2
 * @author      WBCE Community
 * @copyright   2026 WBCE Community
 * @license     MIT License
 */

// prevent this file from being accessed directly
if (!defined('WB_PATH')) {
    exit('Direct access to this file is not allowed');
}

// Clean old cache files on upgrade
$cache_files = [
    WB_PATH . '/temp/.wbce_releases_cache.json',
    WB_PATH . '/temp/.wbce_requirements_cache.json'
];

foreach ($cache_files as $cache_file) {
    if (file_exists($cache_file)) {
        @unlink($cache_file);
    }
}
