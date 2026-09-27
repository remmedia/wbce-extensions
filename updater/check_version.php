<?php
/**
 * Updater - Version Check
 *
 * Prüft GitHub API auf verfügbare WBCE Updates
 *
 * @category    module
 * @package     updater
 * @version     1.0.2
 * @author      WBCE Community
 * @copyright   2026 WBCE Community
 * @license     MIT License
 */

// Error handling - don't display errors, capture them
$originalErrorReporting = error_reporting();
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Include WBCE framework - session_start() is called inside config.php
$configFile = dirname(dirname(dirname(__FILE__))) . '/config.php';
if (!file_exists($configFile)) {
    header('Content-Type: application/json; charset=UTF-8');
    header('X-Content-Type-Options: nosniff');
    exit(json_encode(['error' => 'Configuration file not found']));
}
require $configFile;
require_once WB_PATH . '/framework/Admin.php';

// Output buffering starts AFTER session_start() to avoid session/cookie interference
// on hosting environments with custom PHP-FPM pool configurations (e.g. all-inkl.com)
ob_start();

// Load central configuration
require_once __DIR__ . '/config_defaults.php';

$language = defined('LANGUAGE') ? strtoupper((string) LANGUAGE) : 'EN';
$languageFile = __DIR__ . '/languages/' . $language . '.php';
if (!is_file($languageFile)) {
    $languageFile = __DIR__ . '/languages/EN.php';
}
$LANG = array();
require $languageFile;

// Security check: Admin only (without header output for AJAX)
$admin = new admin('Admintools', 'admintools', false, false);

if (!$admin->is_authenticated() || !$admin->isAdmin()) {
    ob_end_clean();
    http_response_code(403);
    header('Content-Type: application/json; charset=UTF-8');
    header('X-Content-Type-Options: nosniff');
    exit(json_encode(['error' => $LANG['ERROR_UNAUTHORIZED']]));
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

// Set JSON header early
header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

// GitHub API endpoint (from central config)
$github_api = WBCE_UPDATER_GITHUB_API;

// Cache file path (unified location)
$cache_file = WBCE_UPDATER_CACHE_DIR . '/.wbce_releases_cache.json';
$cache_lifetime = WBCE_UPDATER_RELEASES_CACHE;

try {
    // At least one HTTPS transport is needed. cURL is preferred because it
    // also works when providers disable allow_url_fopen.
    if (!function_exists('curl_init') && !filter_var(ini_get('allow_url_fopen'), FILTER_VALIDATE_BOOLEAN)) {
        throw new Exception($LANG['ERROR_URL_FOPEN_DISABLED']);
    }

    // Check cache first
    $use_cache = false;
    if (file_exists($cache_file)) {
        $cache_age = time() - filemtime($cache_file);
        if ($cache_age < $cache_lifetime) {
            $response = file_get_contents($cache_file);
            if ($response !== false && !empty($response) && is_array(json_decode($response, true))) {
                $use_cache = true;
            }
        }
    }

    // Fetch from GitHub if no valid cache
    if (!$use_cache) {
        // Create context for API request with configurable timeout
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => "User-Agent: WBCE-Updater/1.0\r\nAccept: application/vnd.github.v3+json\r\nAccept-Encoding: gzip",
                'timeout' => WBCE_UPDATER_HTTP_TIMEOUT,
                'ignore_errors' => true
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true
            ]
        ]);

        // Retry mechanism: try twice with delay
        $response = false;
        $max_retries = 2;
        $last_error = '';

        for ($attempt = 1; $attempt <= $max_retries; $attempt++) {
            $responseHeaders = array();
            $status_code = 0;
            if (function_exists('curl_init')) {
                $curl = curl_init($github_api);
                if ($curl !== false) {
                    curl_setopt_array($curl, array(
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_CONNECTTIMEOUT => min(10, WBCE_UPDATER_HTTP_TIMEOUT),
                        CURLOPT_TIMEOUT => WBCE_UPDATER_HTTP_TIMEOUT,
                        CURLOPT_FOLLOWLOCATION => false,
                        CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
                        CURLOPT_HTTPHEADER => array('Accept: application/vnd.github.v3+json'),
                        CURLOPT_USERAGENT => 'WBCE-Updater/1.0.28',
                        CURLOPT_ENCODING => '',
                    ));
                    $response = curl_exec($curl);
                    $status_code = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
                    $curl_error = curl_error($curl);
                    if (PHP_VERSION_ID < 80500) curl_close($curl);
                    if (!is_string($response) || strlen($response) > 4194304 || $status_code < 200 || $status_code >= 300) {
                        $response = false;
                        $last_error = $curl_error !== '' ? $curl_error : sprintf($LANG['ERROR_RELEASE_API_STATUS'], $status_code);
                    }
                }
            } else {
                $response = @file_get_contents($github_api, false, $context, 0, 4194305);
                // PHP 8.4 provides an explicit accessor; PHP 8.2/8.3 expose
                // the response headers in the local scope.
                if (function_exists('http_get_last_response_headers')) {
                    $responseHeaders = http_get_last_response_headers();
                } else {
                    $requestScope = get_defined_vars();
                    $responseHeaders = isset($requestScope['http_response_header']) && is_array($requestScope['http_response_header'])
                        ? $requestScope['http_response_header'] : array();
                    unset($requestScope);
                }
            }

            if ($response !== false && !empty($response)) {
                // Decompress if GitHub sent gzip-encoded response
                if (!empty($responseHeaders) && function_exists('gzdecode')) {
                    foreach ($responseHeaders as $hdr) {
                        if (stripos($hdr, 'Content-Encoding:') !== false && stripos($hdr, 'gzip') !== false) {
                            $decoded = gzdecode($response);
                            if ($decoded !== false) {
                                $response = $decoded;
                            }
                            break;
                        }
                    }
                }
                // Never cache proxy/login/error HTML as a release response.
                if (is_array(json_decode($response, true))) {
                    @file_put_contents($cache_file, $response, LOCK_EX);
                    break;
                }
                $last_error = $LANG['ERROR_RELEASE_API_INVALID'];
                $response = false;
            }

            // Get error details
            if ($last_error === '') {
                $error = error_get_last();
                $last_error = isset($error['message']) ? $error['message'] : $LANG['ERROR_UNKNOWN'];
            }

            // Check HTTP response code
            if (!empty($responseHeaders)) {
                foreach ($responseHeaders as $header) {
                    if (preg_match('/^HTTP\/\d\.\d\s+(\d+)/', $header, $matches)) {
                        $status_code = (int)$matches[1];

                        // Don't retry on client errors (4xx)
                        if ($status_code >= 400 && $status_code < 500) {
                            throw new Exception(sprintf($LANG['ERROR_RELEASE_API_STATUS'], $status_code));
                        }

                        // 5xx errors: retry
                        if ($status_code >= 500 && $attempt < $max_retries) {
                            continue;
                        }
                    }
                }
            }

        }

        if ($response === false || empty($response)) {
            // Try to use old cache as fallback
            if (file_exists($cache_file)) {
                $response = file_get_contents($cache_file);
                if ($response !== false && !empty($response)) {
                    $use_cache = true;
                    // Add warning that cache is used
                } else {
                    // Generic error message to avoid information disclosure
                    throw new Exception($LANG['ERROR_RELEASE_API_UNAVAILABLE']);
                }
            } else {
                throw new Exception($LANG['ERROR_RELEASE_API_UNAVAILABLE']);
            }
        }
    }

    $releases = json_decode($response, true);

    if (!is_array($releases)) {
        throw new Exception($LANG['ERROR_RELEASE_API_INVALID']);
    }

    // Filter and prepare available updates
    $available_updates = [];

    foreach ($releases as $release) {
        if (!is_array($release) || !isset($release['tag_name']) || !is_scalar($release['tag_name'])) {
            continue;
        }
        // Skip drafts and pre-releases
        if (!empty($release['draft']) || !empty($release['prerelease'])) {
            continue;
        }

        // Extract version from tag_name (e.g., "1.6.5" from "1.6.5" or "v1.6.5")
        $version = ltrim(trim((string)$release['tag_name']), 'vV');
        if (!preg_match('/^\d+\.\d+(?:\.\d+)?(?:[-+][0-9A-Za-z][0-9A-Za-z.-]*)?$/', $version)) {
            continue;
        }

        // Find download URL for zip file and extract digest (checksum)
        $download_url = null;
        $checksum = null;
        if (isset($release['assets']) && is_array($release['assets'])) {
            foreach ($release['assets'] as $asset) {
                if (!is_array($asset) || !isset($asset['name'], $asset['browser_download_url'])
                    || !is_scalar($asset['name']) || !is_scalar($asset['browser_download_url'])
                    || !preg_match('/\.zip$/i', (string)$asset['name'])) {
                    continue;
                }
                $candidateUrl = trim((string)$asset['browser_download_url']);
                $candidateParts = parse_url($candidateUrl);
                if (is_array($candidateParts) && ($candidateParts['scheme'] ?? '') === 'https'
                    && isset($candidateParts['host'])
                    && preg_match('/^(?:api\.)?github\.com$/i', (string)$candidateParts['host'])) {
                    $download_url = $candidateUrl;
                    // Extract digest field (available since June 2025)
                    if (isset($asset['digest']) && is_scalar($asset['digest'])) {
                        $checksum = (string)$asset['digest']; // Format: "sha256:HASH"
                    }
                    break;
                }
            }
        }

        // If no asset found, try zipball_url as fallback
        if ($download_url === null && isset($release['zipball_url']) && is_scalar($release['zipball_url'])) {
            $candidateUrl = trim((string)$release['zipball_url']);
            $candidateParts = parse_url($candidateUrl);
            if (is_array($candidateParts) && ($candidateParts['scheme'] ?? '') === 'https'
                && isset($candidateParts['host'])
                && preg_match('/^(?:api\.)?github\.com$/i', (string)$candidateParts['host'])) {
                $download_url = $candidateUrl;
            }
        }

        $available_updates[] = [
            'version' => $version,
            'name' => isset($release['name']) && is_scalar($release['name']) ? (string)$release['name'] : $version,
            'published_at' => isset($release['published_at']) && is_scalar($release['published_at']) ? (string)$release['published_at'] : '',
            'download_url' => $download_url,
            'checksum' => $checksum,  // NEW: Checksum from digest field
            'body' => isset($release['body']) && is_scalar($release['body']) ? (string)$release['body'] : '', // Release notes
            'html_url' => isset($release['html_url']) && is_scalar($release['html_url']) ? (string)$release['html_url'] : ''
        ];
    }

    // Return JSON response
    $result = [
        'success' => true,
        'updates' => $available_updates
    ];

    // Add cache info if cached data was used
    if ($use_cache && file_exists($cache_file)) {
        $cache_age = time() - filemtime($cache_file);
        $result['cached'] = true;
        $result['cache_age'] = $cache_age;
    }

    echo json_encode($result, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
}
