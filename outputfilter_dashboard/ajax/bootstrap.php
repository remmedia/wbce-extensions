<?php

ob_start();
$bootstrapLanguage = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? strtoupper(substr((string) $_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2)) : 'EN';
$LANG = array();
require dirname(__DIR__) . '/languages/EN.php';
if ($bootstrapLanguage !== 'EN' && preg_match('/^[A-Z]{2}$/', $bootstrapLanguage) && is_readable(dirname(__DIR__) . '/languages/' . $bootstrapLanguage . '.php')) {
    require dirname(__DIR__) . '/languages/' . $bootstrapLanguage . '.php';
}
$bootstrapText = isset($LANG['MOD_OPF']) && is_array($LANG['MOD_OPF']) ? $LANG['MOD_OPF'] : array();

function opf_ajax_reply($success, $message, $status = 200, array $extra = array())
{
    global $admin;
    if (!isset($extra['ftan']) && isset($admin) && is_object($admin) && method_exists($admin, 'getFTAN')) {
        $extra['ftan'] = $admin->getFTAN(false);
    }
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    header('Content-Type: application/json; charset=UTF-8');
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    http_response_code((int) $status);
    $payload = json_encode(
        array_merge(array('success' => (bool) $success, 'message' => (string) $message), $extra),
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
    );
    echo $payload === false ? '{"success":false,"message":"JSON encoding failed."}' : $payload;
    exit;
}

$config = dirname(__DIR__, 3) . '/config.php';
if (!is_readable($config)) {
    opf_ajax_reply(false, isset($bootstrapText['TXT_AJAX_CONFIG_UNAVAILABLE']) ? $bootstrapText['TXT_AJAX_CONFIG_UNAVAILABLE'] : 'Configuration unavailable.', 500);
}
require_once $config;
require_once WB_PATH . '/framework/class.admin.php';

$admin = new admin('admintools', 'admintools', false);
$lang = defined('LANGUAGE') ? strtoupper((string) LANGUAGE) : 'EN';
$base = array();
$translated = array();
if (is_readable(WB_PATH . '/modules/outputfilter_dashboard/languages/EN.php')) {
    require WB_PATH . '/modules/outputfilter_dashboard/languages/EN.php';
    $base = isset($LANG['MOD_OPF']) && is_array($LANG['MOD_OPF']) ? $LANG['MOD_OPF'] : array();
}
if ($lang !== 'EN' && preg_match('/^[A-Z]{2}$/', $lang) && is_readable(WB_PATH . '/modules/outputfilter_dashboard/languages/' . $lang . '.php')) {
    require WB_PATH . '/modules/outputfilter_dashboard/languages/' . $lang . '.php';
    $translated = isset($LANG['MOD_OPF']) && is_array($LANG['MOD_OPF']) ? $LANG['MOD_OPF'] : array();
}
$opfAjaxLanguage = array_merge($base, $translated);
function opf_ajax_text($key)
{
    global $opfAjaxLanguage;
    return isset($opfAjaxLanguage[$key]) ? $opfAjaxLanguage[$key] : $key;
}

if (!$admin->is_authenticated() || !$admin->get_permission('outputfilter_dashboard', 'module')) {
    opf_ajax_reply(false, opf_ajax_text('TXT_AJAX_FORBIDDEN'), 403);
}
if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST' || !$admin->checkFTAN()) {
    opf_ajax_reply(false, opf_ajax_text('TXT_AJAX_SECURITY'), 403);
}
