<?php
/**
 * ALTCHA challenge endpoint — hardened for WBCE.
 *
 * MUST return clean JSON and nothing else. Uses output buffering to catch
 * any stray output (notices, warnings, BOM) that PHP or included files emit
 * before we're ready to output JSON.
 */

// ── 1. Start output buffer immediately — catches any stray output ─────────────
ob_start();

// ── 2. Bootstrap WBCE ─────────────────────────────────────────────────────────
$_config_found = false;
$_dir = __DIR__;
for ($i = 0; $i < 6; $i++) {
    $_dir = dirname($_dir);
    if (file_exists($_dir . '/config.php')) {
        require_once $_dir . '/config.php';
        $_config_found = true;
        break;
    }
}

// ── 3. Helper: bail with a JSON error (discards any buffered stray output) ────
function altcha_json_error(int $httpCode, string $message): never
{
    if (ob_get_level() > 0) {
        ob_end_clean(); // discard any stray output before us
    }
    http_response_code($httpCode);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    header('X-Content-Type-Options: nosniff');
    $json = json_encode(['error' => $message], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP);
    echo is_string($json) ? $json : '{"error":"response_encoding_failed"}';
    exit;
}

if (!$_config_found || !defined('WB_PATH')) {
    altcha_json_error(500, 'wbce_bootstrap_failed');
}

// ── 4. Load AltchaLib ─────────────────────────────────────────────────────────
$_lib = WB_PATH . '/modules/captcha_control/altcha/AltchaLib.php';
if (!file_exists($_lib)) {
    altcha_json_error(500, 'captcha_runtime_missing');
}
require_once $_lib;

/** Read one setting on WBCE 1.7 and on the removable 1.6.8 compatibility path. */
function altcha_db_value($database, string $sql)
{
    if (!is_object($database)) {
        return null;
    }
    if (is_callable([$database, 'fetchValue'])) {
        return $database->fetchValue($sql);
    }
    if (is_callable([$database, 'get_one'])) {
        return $database->get_one($sql);
    }
    if (!is_callable([$database, 'query'])) {
        return null;
    }
    $result = $database->query($sql);
    if (is_object($result) && is_callable([$result, 'fetchRow'])) {
        $row = $result->fetchRow(defined('MYSQLI_NUM') ? MYSQLI_NUM : 2);
        return is_array($row) ? ($row[0] ?? null) : null;
    }
    if (is_object($result) && is_callable([$result, 'fetch_row'])) {
        $row = $result->fetch_row();
        return is_array($row) ? ($row[0] ?? null) : null;
    }
    return null;
}

// ── 5. Load ALTCHA provider settings ─────────────────────────────────────────
//
// Settings::setup() defines CAPTCHA_ALTCHA as a JSON string constant.
// Fall back to a direct DB read if the constant is not yet available.
//
$altchaCfg = [];

if (defined('CAPTCHA_ALTCHA') && CAPTCHA_ALTCHA !== '') {
    // Happy path: constant defined by Settings::setup() during WBCE bootstrap.
    $altchaCfg = json_decode(CAPTCHA_ALTCHA, true) ?? [];

} elseif (isset($database)) {
    // Fallback: read directly from the settings table.
    $raw = altcha_db_value($database,
        "SELECT `value` FROM `{TP}settings` WHERE `name` = 'captcha_altcha'"
    );
    if (!empty($raw)) {
        $altchaCfg = json_decode($raw, true) ?? [];
    }
}

$hmacKey   = $altchaCfg['hmac_key'] ?? '';
$maxNumber = (int)($altchaCfg['max'] ?? 50000) ?: 50000;
$ttl       = (int)($altchaCfg['ttl'] ?? 600)   ?: 600;

// Backward-compat: installations mid-upgrade may still have the old flat key
if (empty($hmacKey) && isset($database)) {
    $hmacKey = (string)(altcha_db_value($database,
        "SELECT `value` FROM `{TP}settings` WHERE `name` = 'captcha_altcha_hmac_key'"
    ) ?? '');
}

if (empty($hmacKey)) {
    altcha_json_error(500, 'captcha_not_configured');
}

// ── 7. Light rate limiting (session-based, per minute) ────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}
$_bucket = 'altcha_req_' . date('YmdHi'); // new bucket every minute
$_count  = (int)($_SESSION[$_bucket] ?? 0);
if ($_count >= 60) {
    altcha_json_error(429, 'rate_limit_exceeded');
}
$_SESSION[$_bucket] = $_count + 1;

// ── 8. Generate challenge and respond ─────────────────────────────────────────
$strayOutput = ob_get_clean(); // discard anything emitted before this point

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');

try {
    $lib = new AltchaLib($hmacKey, $maxNumber, $ttl);
    $json = json_encode($lib->createChallenge(), JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP);
    if (!is_string($json)) {
        altcha_json_error(500, 'response_encoding_failed');
    }
    echo $json;
} catch (Throwable $error) {
    altcha_json_error(500, 'challenge_generation_failed');
}
exit;
