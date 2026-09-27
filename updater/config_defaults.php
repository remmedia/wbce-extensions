<?php
/**
 * WBCE Updater - Configuration Defaults
 *
 * Central configuration file for module constants and settings
 *
 * @category    module
 * @package     updater
 * @version     1.0.2
 * @author      WBCE Community
 * @copyright   2026 WBCE Community
 * @license     MIT License
 */

// Prevent direct access
if (!defined('WB_PATH')) {
    exit("Cannot access this file directly");
}

// ============================================================================
// EXTERNAL SERVICE URLs
// ============================================================================

// GitHub API endpoint for WBCE releases
if (!defined('WBCE_UPDATER_GITHUB_API')) {
    define('WBCE_UPDATER_GITHUB_API', 'https://api.github.com/repos/WBCE/WBCE_CMS/releases');
}

// PHP requirements JSON file URL
if (!defined('WBCE_UPDATER_REQUIREMENTS_URL')) {
    define('WBCE_UPDATER_REQUIREMENTS_URL', 'https://wbce.org/media/wbce_php_requirements.json');
}

// Checksums JSON file URL
if (!defined('WBCE_UPDATER_CHECKSUMS_URL')) {
    define('WBCE_UPDATER_CHECKSUMS_URL', 'https://wbce.org/media/checksums.json');
}

// ============================================================================
// CACHE SETTINGS
// ============================================================================

// Cache lifetime for GitHub releases (15 minutes)
if (!defined('WBCE_UPDATER_RELEASES_CACHE')) {
    define('WBCE_UPDATER_RELEASES_CACHE', 900);
}

// Cache lifetime for PHP requirements (1 hour)
if (!defined('WBCE_UPDATER_REQUIREMENTS_CACHE')) {
    define('WBCE_UPDATER_REQUIREMENTS_CACHE', 3600);
}

// Cache directory (unified location for all cache files)
if (!defined('WBCE_UPDATER_CACHE_DIR')) {
    define('WBCE_UPDATER_CACHE_DIR', WB_PATH . '/temp');
}

// ============================================================================
// SECURITY SETTINGS
// ============================================================================

// Maximum assembled upload package size (512 MB). Individual HTTP requests
// remain below the PHP upload/post limits through chunking.
if (!defined('WBCE_UPDATER_MAX_UPLOAD_SIZE')) {
    define('WBCE_UPDATER_MAX_UPLOAD_SIZE', 512 * 1024 * 1024);
}

// Allowed download hosts (whitelist)
if (!defined('WBCE_UPDATER_ALLOWED_HOSTS')) {
    define('WBCE_UPDATER_ALLOWED_HOSTS', 'github.com,api.github.com');
}

// HTTP request timeout (seconds)
if (!defined('WBCE_UPDATER_HTTP_TIMEOUT')) {
    define('WBCE_UPDATER_HTTP_TIMEOUT', 30);
}

// Downloaded packages must carry a valid SHA-256 checksum. This setting is
// retained for compatibility, but remote downloads always fail closed.
// Manually uploaded packages are deliberately exempt.
if (!defined('WBCE_UPDATER_VERIFY_CHECKSUMS')) {
    define('WBCE_UPDATER_VERIFY_CHECKSUMS', true);
}

// ============================================================================
// DEBUG MODE
// ============================================================================

// Enable detailed error messages (should be false in production)
if (!defined('WBCE_UPDATER_DEBUG')) {
    define('WBCE_UPDATER_DEBUG', false);
}

// ============================================================================
// USER CONFIG OVERRIDES  (user_config.php)
// ============================================================================
// user_config.php wird NICHT in Release-ZIPs mitgeliefert und überlebt Updates.
// Sie muss manuell aus user_config.default.php kopiert werden.
//
// Konstanten oder Variablen aus dieser Datei, die dauerhaft über Updates hinweg
// angepasst bleiben sollen, müssen in die user_config.php verschoben werden –
// denn diese Datei (config_defaults.php) wird bei jedem Update überschrieben.
$updater_custom_source_url = '';
$updater_disabled = false;
if (file_exists(__DIR__ . '/user_config.php')) {
    require_once __DIR__ . '/user_config.php';
}
