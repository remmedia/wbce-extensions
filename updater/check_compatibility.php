<?php
/**
 * WBCE Updater - Compatibility Check Endpoint
 *
 * AJAX endpoint for checking PHP compatibility with target WBCE version
 *
 * @category    module
 * @package     updater
 * @version     1.0.2
 * @author      WBCE Community
 * @copyright   2026 WBCE Community
 * @license     MIT License
 */

// Start output buffering to catch any unwanted output
ob_start();

// Error handling - don't display errors
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Include WBCE framework
$configFile = dirname(dirname(dirname(__FILE__))) . '/config.php';
if (!file_exists($configFile)) {
    ob_end_clean();
    header('Content-Type: application/json; charset=UTF-8');
    header('X-Content-Type-Options: nosniff');
    exit(json_encode(['error' => 'Configuration file not found']));
}
require $configFile;
require_once WB_PATH . '/framework/Admin.php';

// Load central configuration
require_once __DIR__ . '/config_defaults.php';

// Include compatibility checker
require_once __DIR__ . '/compatibility_checker.php';

$language = defined('LANGUAGE') ? strtoupper((string) LANGUAGE) : 'EN';
$languageFile = __DIR__ . '/languages/' . $language . '.php';
if (!is_file($languageFile)) $languageFile = __DIR__ . '/languages/EN.php';
$LANG = array(); require $languageFile;

// Security check: Admin only (without header output for AJAX)
$admin = new admin('Admintools', 'admintools', false, false);

// CSRF protection: Check FTAN token
$ftan_valid = method_exists($admin, 'checkFTAN') && $admin->checkFTAN();

// Fallback for WBCE 1.4.x: Check session-based authentication
$session_valid = $admin->is_authenticated() && $admin->isAdmin();

if (!$session_valid || !$ftan_valid) {
    ob_end_clean();
    http_response_code(403);
    header('Content-Type: application/json; charset=UTF-8');
    header('X-Content-Type-Options: nosniff');
    exit(json_encode(['error' => $LANG['AJAX_INVALID_TOKEN']]));
}

if (!empty($updater_disabled)) {
    ob_end_clean();
    http_response_code(403);
    header('Content-Type: application/json; charset=UTF-8');
    header('X-Content-Type-Options: nosniff');
    exit(json_encode(['error' => $LANG['TOOL_DISABLED']]));
}

// Clear any output that might have been generated
ob_end_clean();

// Set JSON header
header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

try {
    // Get target version from GET or POST
    $rawVersion = $_POST['version'] ?? $_GET['version'] ?? '';
    $target_version = is_scalar($rawVersion) ? trim((string) $rawVersion) : '';

    if (empty($target_version)) {
        throw new Exception($LANG['AJAX_VERSION_MISSING']);
    }

    // Security: Validate version format (only digits, dots, and optional 'v' prefix)
    if (!preg_match('/^v?\d+\.\d+(?:\.\d+)?(?:[-+][0-9A-Za-z][0-9A-Za-z.-]*)?$/', $target_version)) {
        throw new Exception(sprintf($LANG['AJAX_VERSION_INVALID'], $target_version));
    }

    // Check PHP compatibility
    $result = checkPhpCompatibility($target_version);

    // Add current PHP version info
    $result['php_version'] = PHP_VERSION;

    // Check EOL status
    $requirements = loadPhpRequirements();
    if ($requirements !== false) {
        $eolCheck = checkPhpEol(PHP_VERSION, $requirements);
        $result['php_eol'] = $eolCheck;
    }

    echo json_encode($result, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode([
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
}
