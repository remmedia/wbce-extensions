<?php
defined('WB_PATH') or die('No direct access');

if (!function_exists('wbce_add_action')) {
    $bridge = WB_PATH . '/modules/wbce_hook_bridge/preinit.php';
    if (is_file($bridge)) require_once $bridge;
}
if (!function_exists('wbce_add_action') || !function_exists('wbce_add_filter')) return;
if (defined('WBCE_FORCE_PASSWORD_CHANGE_REGISTERED')) return;
define('WBCE_FORCE_PASSWORD_CHANGE_REGISTERED', true);

require_once __DIR__ . '/Service.php';
require_once __DIR__ . '/Language.php';
global $database;
$service = new WbceForcePasswordChangeService($database);

// On 1.6.8 the bridge supplies the same user-form and mutation hooks that are
// native in the new core. It stays inactive where the core already provides them.
if (class_exists('WbceHookBridge')) WbceHookBridge::enableUserFormHooks();

wbce_add_filter('admin.user.form.sections', static function ($html, $userId, $isNew) use ($service) {
    $checked = $isNew || $service->isRequired((int) $userId);
    $fallback = '<link rel="stylesheet" href="' . htmlspecialchars(WB_URL . '/modules/force_password_change/admin.css?v=2.0.18', ENT_QUOTES, 'UTF-8') . '">'
        . '<div class="force-password-change-option content-box wbce-admin-card" data-password-option="force-change">'
        . '<input type="hidden" name="force_password_change_present" value="1">'
        . '<label><input class="force-password-change-checkbox" type="checkbox" name="force_password_change" value="1"' . ($checked ? ' checked' : '') . '>'
        . '<span><strong>' . htmlspecialchars(fpc_t('option_title'), ENT_QUOTES, 'UTF-8') . '</strong><br><small>'
        . htmlspecialchars($isNew ? fpc_t('option_new_help') : fpc_t('option_existing_help'), ENT_QUOTES, 'UTF-8')
        . '</small></span></label></div>';
    if (function_exists('getTwig')) {
        try {
            $twig = getTwig(__DIR__ . '/templates/');
            return $html . $twig->load('user_option.twig')->render(array(
                'checked' => $checked,
                'is_new' => (bool) $isNew,
                'option_title' => fpc_t('option_title'),
                'option_help' => $isNew ? fpc_t('option_new_help') : fpc_t('option_existing_help'),
                'wb_url' => WB_URL,
                'module_version' => '2.0.18',
            ));
        } catch (Throwable $ignored) {
            // The module-owned HTML path keeps older WBCE installations usable.
        }
    }
    return $html . $fallback;
}, 10);

$persist = static function ($userId, $unused = array(), $input = array()) use ($service) {
    if (empty($input['force_password_change_present'])) return;
    $service->setRequired((int) $userId, !empty($input['force_password_change']), WbceForcePasswordChangeCompatibility::currentUserId());
};
wbce_add_action('user.created', $persist, 10);
wbce_add_action('user.updated', $persist, 10);
wbce_add_action('user.deleted', static function ($userId) use ($service) {
    $service->removeUser((int) $userId);
}, 10);

wbce_add_action('user.password.reset_completed', static function ($userId) use ($service) {
    $service->clear((int) $userId);
}, 10);

wbce_add_filter('auth.login.redirect', static function ($redirect, $user) use ($service) {
    $userId = is_array($user) && isset($user['user_id']) ? (int) $user['user_id'] : WbceForcePasswordChangeCompatibility::currentUserId();
    if (!$service->isRequired($userId)) return $redirect;
    $_SESSION['FORCE_PASSWORD_CHANGE_RETURN'] = WbceForcePasswordChangeCompatibility::safeRedirect($redirect, ADMIN_URL . '/start/index.php');
    return WB_URL . '/modules/force_password_change/change.php';
}, 10);

// This guard also supplies the redirect on unmodified WBCE 1.6.8, whose login
// controller cannot dispatch auth.login.redirect itself.
$userId = WbceForcePasswordChangeCompatibility::currentUserId();
if ($userId > 0 && $service->isRequired($userId) && !headers_sent() && PHP_SAPI !== 'cli') {
    $path = (string) parse_url(isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '', PHP_URL_PATH);
    $changePath = (string) parse_url(WB_URL . '/modules/force_password_change/change.php', PHP_URL_PATH);
    $logoutPath = (string) parse_url(ADMIN_URL . '/logout/', PHP_URL_PATH);
    $allowed = $path === $changePath || strpos($path, rtrim($logoutPath, '/')) === 0;
    if (!$allowed) {
        if (!isset($_SESSION['FORCE_PASSWORD_CHANGE_RETURN']) && isset($_SERVER['REQUEST_URI'])) {
            $_SESSION['FORCE_PASSWORD_CHANGE_RETURN'] = WbceForcePasswordChangeCompatibility::safeRedirect((string)$_SERVER['REQUEST_URI'], ADMIN_URL . '/start/index.php');
        }
        if (in_array(isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET', array('GET', 'HEAD'), true)) {
            header('Location: ' . WB_URL . '/modules/force_password_change/change.php');
        } else {
            http_response_code(403);
            header('Content-Type: text/plain; charset=UTF-8');
            echo fpc_t('change_required_first');
        }
        exit;
    }
}
