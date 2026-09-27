<?php
/**
 * captcha_control — tool.php
 *
 * Admin-Tool entry point. Handles save and renders the Twig form.
 *
 * @copyright WBCE Project (2015-)
 * @license   GNU GPL2 (or any later version)
 */

defined('WB_PATH') or exit('No direct access allowed');
$admin->get_permission('admintools') or die(header('Location: ../../index.php'));
require_once __DIR__ . '/Compatibility.php';
$captchaSelectedLanguage = isset($CAPTCHA) && is_array($CAPTCHA) ? $CAPTCHA : [];
$CAPTCHA = [];
include __DIR__ . '/languages/EN.php';
$CAPTCHA = array_merge($CAPTCHA, $captchaSelectedLanguage);

$returnUrl = ADMIN_URL . '/admintools/tool.php?tool=captcha_control';
// An asynchronous save must never be contaminated by CMS notices or provider output.
$captchaIsAjax = strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest'
    || (string)($_POST['captcha_ajax'] ?? '') === '1'
    || str_contains(strtolower((string)($_SERVER['HTTP_ACCEPT'] ?? '')), 'application/json');
$captchaAjaxBufferLevel = $captchaIsAjax ? ob_get_level() : null;
if ($captchaIsAjax) { ob_start(); }
$captchaJson = static function (array $payload): string {
    $json = json_encode(
        $payload,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
    );
    return is_string($json) ? $json : '{"success":false,"message":"JSON encoding failed"}';
};
$captchaRespondJson = static function (array $payload) use ($captchaJson, $captchaIsAjax, $captchaAjaxBufferLevel): void {
    if ($captchaIsAjax && $captchaAjaxBufferLevel !== null) {
        while (ob_get_level() > $captchaAjaxBufferLevel) { ob_end_clean(); }
    }
    http_response_code(!empty($payload['success']) ? 200 : (int)($payload['status'] ?? 400));
    header('Content-Type: application/json; charset=UTF-8');
    header('X-Content-Type-Options: nosniff');
    echo $captchaJson($payload);
    exit;
};
$providerDefinitions = [];
if (function_exists('wbce_captcha_providers')) {
    foreach (wbce_captcha_providers() as $providerId => $definition) {
        if (is_array($definition) && empty($definition['legacy'])) $providerDefinitions[(string)$providerId] = $definition;
    }
}
if ($providerDefinitions === []) {
    $providerDefinitions['altcha'] = [
        'id' => 'altcha',
        'name' => $CAPTCHA['FALLBACK_PROVIDER_NAME'],
        'description' => $CAPTCHA['FALLBACK_PROVIDER_DESCRIPTION'],
        'legacy' => false,
        'fallback' => true,
    ];
}
$configuredProvider = (string)Settings::get('captcha_type', defined('CAPTCHA_TYPE') ? CAPTCHA_TYPE : 'altcha');
$currentProvider = isset($providerDefinitions[$configuredProvider]) ? $configuredProvider : (string)array_key_first($providerDefinitions);

// ── Save ──────────────────────────────────────────────────────────────────────
if (isset($_POST['save_settings'])) {
    $isAjax = $captchaIsAjax;
    if (!$admin->checkFTAN()) {
        if ($isAjax) {
            $captchaRespondJson(['success'=>false, 'status'=>403, 'message'=>$MESSAGE['GENERIC_SECURITY_ACCESS']]);
        }
        (new Alerts())->sessionToast($MESSAGE['GENERIC_SECURITY_ACCESS'], 'error');
        header('Location: ' . $returnUrl);
        exit;
    }

    // Flat control settings — each becomes a PHP constant via Settings::setup()
    $e1 = Settings::set('enabled_captcha', ($_POST['enabled_captcha'] ?? '0') === '1' ? 'true' : 'false');
    $e2 = Settings::set('enabled_asp',     ($_POST['enabled_asp']     ?? '0') === '1' ? 'true' : 'false');
    $requestedProvider = (string)($_POST['captcha_type'] ?? $currentProvider);
    $selectedProvider = isset($providerDefinitions[$requestedProvider]) ? $requestedProvider : $currentProvider;
    $e3 = Settings::set('captcha_type', $selectedProvider);
    $eLogin = Settings::set('captcha_login_mode', in_array($_POST['captcha_login_mode'] ?? '', ['off','after_failures','always'], true) ? $_POST['captcha_login_mode'] : 'after_failures');
    $eReset = Settings::set('captcha_password_reset', !empty($_POST['captcha_password_reset']) ? '1' : '0');

    // The built-in ALTCHA fallback owns these settings only when no external
    // provider module is installed. External providers persist via the hook.
    $isInternalAltcha = !empty($providerDefinitions[$selectedProvider]['fallback']);
    $altcha  = Captcha::getAltchaCfg();
    $hmacKey = $altcha['hmac_key'] ?? '';
    if (empty($hmacKey)) {
        require_once WB_PATH . '/modules/captcha_control/altcha/AltchaLib.php';
        $hmacKey = AltchaLib::generateHmacKey();
    }

    // Sanitize widget customization fields from POST
    $allowedAuto   = ['off', 'onload', 'onsubmit'];
    $allowedRadius = ['', '0px', '4px', '12px'];

    $postAuto   = in_array($_POST['altcha_auto']          ?? '', $allowedAuto,   true) ? $_POST['altcha_auto']   : 'off';
    $postDelay  = max(0, min(3000, (int)($_POST['altcha_delay']   ?? 0)));
    $postHFoot  = ($_POST['altcha_hidefooter'] ?? '0') === '1';
    $postHLogo  = ($_POST['altcha_hidelogo']   ?? '0') === '1';
    $postRadius = in_array($_POST['altcha_border_radius'] ?? '', $allowedRadius, true) ? $_POST['altcha_border_radius'] : '';

    $postBrand   = sanitizeCssColor($_POST['altcha_color_brand']    ?? '');
    $postSuccess = sanitizeCssColor($_POST['altcha_color_success']  ?? '');
    $postBase    = sanitizeCssColor($_POST['altcha_color_base']     ?? '');
    $postCb      = sanitizeCssColor($_POST['altcha_color_checkbox'] ?? '');
    $postText    = sanitizeCssColor($_POST['altcha_color_text']     ?? '');

    $e4 = $isInternalAltcha ? Settings::set('captcha_altcha', json_encode([
        'hmac_key'      => $hmacKey,
        'max'           => $altcha['max'] ?? 50000,
        'ttl'           => $altcha['ttl'] ?? 600,
        'auto'          => $postAuto,
        'delay'         => $postDelay,
        'hidefooter'    => $postHFoot,
        'hidelogo'      => $postHLogo,
        'color_brand'   => $postBrand,
        'color_success' => $postSuccess,
        'color_base'    => $postBase,
        'color_checkbox'=> $postCb,
        'color_text'    => $postText,
        'border_radius' => $postRadius,
    ])) : '';
    if ($isInternalAltcha) Captcha::resetAltchaCfg();
    $providerSaveError = '';
    if (function_exists('wbce_do_action')) {
        try {
            wbce_do_action('captcha.settings.save', $_POST, $selectedProvider);
        } catch (Throwable $providerError) {
            $providerSaveError = $CAPTCHA['SAVE_FAILED'];
            error_log('CAPTCHA provider settings save failed: ' . $providerError->getMessage());
        }
    }

    $saveError = $e1 ?: $e2 ?: $e3 ?: $eLogin ?: $eReset ?: $e4 ?: $providerSaveError;
    if ($isAjax) {
        $captchaRespondJson([
            'success' => !$saveError,
            'message' => $saveError ?: $CAPTCHA['SAVE_SUCCESS'],
            'ftan' => $admin->getFTAN(),
        ]);
    }
    if (class_exists('Alerts')) {
        (new Alerts())->sessionToast($saveError ?: 'MESSAGE:CHANGES_SAVE_SUCCESS', $saveError ? 'error' : 'success');
    } elseif ($saveError) {
        $admin->print_error($saveError, $returnUrl, false);
    } else {
        $admin->print_success(isset($MESSAGE['PAGES_SAVED']) ? $MESSAGE['PAGES_SAVED'] : 'Gespeichert', $returnUrl, false);
    }
    header('Location: ' . $returnUrl.'#config');
    exit;
}


// ── Render ────────────────────────────────────────────────────────────────────
if (function_exists('loadPlugin')) {
    try {
        call_user_func('loadPlugin', 'include/wbeColoris');
    } catch (Throwable $pluginError) {
        // WBCE 1.6.8 can expose the Twig helper without its PHP counterpart.
        // Native colour inputs remain available as the module-owned fallback.
    }
}
$oTwig = null;
if (function_exists('getTwig')) {
    try {
        // A single module-owned path works with both the WBCE 1.6.8 Twig
        // loader and the newer multi-path loader. Optional templates must not
        // be referenced here because they can be uninstalled independently.
        $oTwig = getTwig(__DIR__ . '/twig/');
    } catch (Throwable $twigError) {
        $oTwig = null;
    }
}
if (!$oTwig && function_exists('wbce_render_module_twig')) {
    // The bridge may expose its renderer before WBCE offers getTwig globally.
    // Rendering is performed below after the context has been assembled.
    $oTwig = false;
}
$altchaCfg = Captcha::getAltchaCfg();
// Some output filters remove values from hidden inputs. Keep the FTAN separately
// so the client can restore it immediately before creating FormData.
$captchaFtanPair = explode('=', $admin->getFTAN(false), 2);
$captchaFtanName = $captchaFtanPair[0] ?? 'formtoken';
$captchaFtanValue = $captchaFtanPair[1] ?? '';
$providerSettings = [];
foreach ($providerDefinitions as $providerId => $definition) {
    $sections = function_exists('wbce_apply_array_filters') ? wbce_apply_array_filters('captcha.settings.sections', [], $providerId) : [];
    if ($sections) $providerSettings[$providerId] = implode('', array_map('strval', $sections));
}

$aToTwig = [
    'RETURN_URL'       => $returnUrl,
    'FTAN_NAME'        => $captchaFtanName,
    'FTAN_VALUE'       => $captchaFtanValue,
    'RETURN_TO_TOOLS'  => ADMIN_URL . '/admintools/index.php',
    'IS_HTTPS'         => Captcha::isHttps(),
    'PROVIDERS'        => $providerDefinitions,
    'PROVIDER_SETTINGS'=> $providerSettings,
    'CAPTCHA_TYPE'     => $currentProvider,
    'ENABLED_CAPTCHA'  => (filter_var(defined('ENABLED_CAPTCHA') ? ENABLED_CAPTCHA : Settings::get('enabled_captcha', true), FILTER_VALIDATE_BOOLEAN) ? '1' : '0'),
    'ENABLED_ASP'      => (filter_var(defined('ENABLED_ASP') ? ENABLED_ASP : Settings::get('enabled_asp', true), FILTER_VALIDATE_BOOLEAN) ? '1' : '0'),
    'CAPTCHA_LOGIN_MODE' => (string)Settings::get('captcha_login_mode', 'after_failures'),
    'CAPTCHA_PASSWORD_RESET' => (string)Settings::get('captcha_password_reset', '1') === '1',
    'TEXT_ENABLED'    => isset($TEXT['ENABLED']) ? $TEXT['ENABLED'] : $CAPTCHA['ENABLED'],
    'TEXT_DISABLED'   => isset($TEXT['DISABLED']) ? $TEXT['DISABLED'] : $CAPTCHA['DISABLED'],
    'UI_TEXT_JSON'    => $captchaJson([
        'active' => $CAPTCHA['ACTIVE'], 'available' => $CAPTCHA['AVAILABLE'],
        'saving' => $CAPTCHA['SAVING'], 'saveSuccess' => $CAPTCHA['SAVE_SUCCESS'],
        'saveFailed' => $CAPTCHA['SAVE_FAILED'], 'invalidResponse' => $CAPTCHA['INVALID_RESPONSE'],
        'enabled' => $CAPTCHA['ENABLED'], 'disabled' => $CAPTCHA['DISABLED'],
    ]),
    'ALTCHA'           => [
        'auto'          => $altchaCfg['auto']          ?? 'off',
        'delay'         => (int)($altchaCfg['delay']   ?? 0),
        'hidefooter'    => !empty($altchaCfg['hidefooter']),
        'hidelogo'      => !empty($altchaCfg['hidelogo']),
        'color_brand'   => $altchaCfg['color_brand']    ?? '',
        'color_success' => $altchaCfg['color_success'] ?? '',
        'color_base'    => $altchaCfg['color_base']    ?? '',
        'color_checkbox'=> $altchaCfg['color_checkbox']?? '',
        'color_text'    => $altchaCfg['color_text']    ?? '',
        'border_radius' => $altchaCfg['border_radius'] ?? '',
    ],
];

try {
    if ($oTwig) {
        $oTwig->load('tool.twig')->display($aToTwig);
    } elseif (function_exists('wbce_render_module_twig')) {
        echo wbce_render_module_twig('captcha_control', 'tool.twig', $aToTwig, '<p class="error">'.h($CAPTCHA['TWIG_FAILED']).'</p>');
    } else {
        throw new RuntimeException($CAPTCHA['TWIG_REQUIRED']);
    }
} catch (Throwable $renderError) {
    echo '<div class="captcha-app"><div class="captcha-section"><h3>'.h($CAPTCHA['HEADING']).'</h3><p class="error">'.h($renderError->getMessage()).'</p></div></div>';
}
