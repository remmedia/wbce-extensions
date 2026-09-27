<?php
/**
 * captcha_control — initialize.php
 *
 * Runs on every page load (FE + BE) because call_captcha() is used in both
 * contexts: frontend contact/registration forms and the backend login page.
 *
 * Registers the Captcha class with the WBCE autoloader and defines the
 * call_captcha() public API function.
 *
 * Language strings are NOT loaded here — only tool.php needs them, so they
 * are loaded on-demand there to avoid pointless work on every FE page.
 *
 * Pattern: same approach as CodeMirror_Config/initialize.php.
 */
defined('WB_PATH') or exit('No direct access allowed');
require_once __DIR__ . '/Compatibility.php';
require_once __DIR__ . '/ManagerCompatibility.php';

// WBCE 1.7 provides the hook API itself. On WBCE 1.6.8 the separately
// installable bridge supplies the same API; load it explicitly when the
// initialization order did not already do so.
if (!function_exists('wbce_add_action') || !function_exists('wbce_add_filter')) {
    $captchaHookBridge = WB_PATH . '/modules/wbce_hook_bridge/preinit.php';
    if (is_file($captchaHookBridge)) {
        require_once $captchaHookBridge;
    }
}

// ── 1. Register Captcha class ─────────────────────────────────────────────────
//
// Explicit AddFile wins over the AddDir('/framework/') scan, so even if
// a stale framework/Captcha.php exists it will not be loaded in its place.

WbAuto::AddFile('Captcha', '/modules/captcha_control/Captcha.php');

// ASP remains provider-independent even when callers use WBCE's native
// CaptchaManager API directly instead of call_captcha()/Captcha::verify().
if (function_exists('wbce_add_action')) {
    wbce_add_action('captcha.rendered', static function ($providerId, $context = []) {
        $context = is_array($context) ? $context : [];
        if (Captcha::isAspEnabled() && (string)($context['action'] ?? 'all') !== 'text') {
            echo Captcha::renderHoneypot((string)($context['section_id'] ?? ''));
        }
    }, 20);
}
if (function_exists('wbce_add_filter')) {
    wbce_add_filter('captcha.validation.result', static function ($valid, $providerId = '', $context = []) {
        if (!$valid || !Captcha::isAspEnabled()) {
            return (bool) $valid;
        }
        $context = is_array($context) ? $context : [];
        return Captcha::verifyHoneypotField((string)($context['section_id'] ?? ''));
    }, 20);
}

// This runs only when WBCE asks whether a provider may be uninstalled and
// therefore has no cost during ordinary frontend requests.
if (function_exists('wbce_add_filter')) {
    wbce_add_filter('addon.beforeUninstall', static function ($allowed, $context = null) {
        global $database;
        if (is_array($allowed) && $context === null) {
            $context = $allowed;
            $allowed = true;
        }
        $directory = is_array($context) ? (string)($context['directory'] ?? '') : '';
        $providers = ['captcha_altcha','captcha_cap','captcha_captchafox','captcha_friendly','captcha_icon','captcha_phpcapcha','captcha_recaptcha','captcha_trustcaptcha'];
        if (!in_array($directory, $providers, true) || !is_object($database) || !is_callable([$database, 'query'])) {
            return $allowed;
        }
        $escaped = is_callable([$database, 'escapeString']) ? $database->escapeString($directory) : addslashes($directory);
        $result = $database->query("SELECT COUNT(*) AS total FROM `{TP}addons` WHERE `type`='module' AND `directory` LIKE 'captcha_%' AND `directory`<>'captcha_control' AND `directory`<>'".$escaped."'");
        $row = null;
        if (is_object($result) && is_callable([$result, 'fetchRow'])) {
            $row = $result->fetchRow(defined('MYSQLI_ASSOC') ? MYSQLI_ASSOC : 1);
        } elseif (is_object($result) && is_callable([$result, 'fetch_assoc'])) {
            $row = $result->fetch_assoc();
        }
        return $row && (int)($row['total'] ?? 0) > 0 ? $allowed : false;
    }, 10);
}

// ── 2. Define call_captcha() ──────────────────────────────────────────────────
//
// Guard: the shim at include/captcha/captcha.php also defines this function,
// so the guard prevents a fatal redefinition if both files are loaded.

if (!function_exists('call_captcha')) {
    /**
     * Render the captcha widget.
     *
     * @param string      $action        'all'|'widget'|'image'|'image_iframe'|'input'|'text'
     * @param string      $style         Unused, kept for backward compatibility.
     * @param string      $sec_id        Session key suffix for multiple captchas per page.
     * @param string|null $type_override Ignored — only altcha is available.
     */
    function call_captcha(
        string  $action        = 'all',
        string  $style         = '',
        string  $sec_id        = '',
        ?string $type_override = null
    ): void {
        Captcha::render($action, $style, $sec_id, $type_override);
    }
}

// Login-template fallback for WBCE 1.7 and third-party admin themes. Some
// templates omit the {CAPTCHA} placeholder even though the login controller
// requires CAPTCHA verification. In "always" mode, prepare the widget early
// and inject it only when the rendered login form still contains no CAPTCHA.
// Templates with a working placeholder remain completely unchanged.
if (
    PHP_SAPI !== 'cli'
    && strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'GET'
    && (string) Settings::Get('captcha_login_mode', 'after_failures') === 'always'
    && (!defined('NO_SESSION_COOKIE') || !NO_SESSION_COOKIE)
    && preg_match('~/admin(?:/login(?:/index\.php)?)?/?$~', (string) parse_url(isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '', PHP_URL_PATH))
) {
    ob_start();
    Captcha::render('all', '', 'login');
    $wbceLoginCaptchaFallback = (string) ob_get_clean();
    if ($wbceLoginCaptchaFallback !== '') {
        ob_start(static function ($html) use ($wbceLoginCaptchaFallback) {
            if (
                stripos($html, 'wbce-altcha') !== false
                || stripos($html, 'altcha-widget') !== false
                || stripos($html, 'captcha-math-fallback') !== false
                || stripos($html, 'wbce-captcha-provider') !== false
            ) {
                return $html;
            }
            $widget = '<div class="wbce-login-captcha-fallback">' . $wbceLoginCaptchaFallback . '</div>';
            $updated = preg_replace(
                '~(<button\b[^>]*type=["\']submit["\'][^>]*>|<input\b[^>]*type=["\']submit["\'][^>]*>)~i',
                $widget . '$1',
                $html,
                1
            );
            return is_string($updated) ? $updated : $html;
        });
    }
}
