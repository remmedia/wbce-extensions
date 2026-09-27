<?php
ob_start();
function cmc_reply($success, $message, $status = 200, $extra = array()) {
    http_response_code((int) $status);
    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('X-Content-Type-Options: nosniff');
    $json=json_encode(array_merge(array('success' => (bool) $success, 'message' => (string) $message), is_array($extra) ? $extra : array()), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    echo is_string($json)?$json:'{"success":false}';
    exit;
}
$language = strtoupper(substr((string)($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'EN'), 0, 2));
if (!preg_match('/^[A-Z]{2}$/D', $language)) $language = 'EN';
require __DIR__ . '/languages/EN.php';
$fallback = $CMC_LANG;
$earlyLanguageFile = __DIR__ . '/languages/' . $language . '.php';
if ($language !== 'EN' && is_readable($earlyLanguageFile)) require $earlyLanguageFile;
$CMC_LANG = array_merge($fallback, isset($CMC_LANG) && is_array($CMC_LANG) ? $CMC_LANG : array());
$config = dirname(__DIR__, 2) . '/config.php';
if (!is_readable($config)) { if (ob_get_level()) ob_end_clean(); cmc_reply(false, $CMC_LANG['configuration_unavailable'], 500); }
require_once $config;
require_once WB_PATH . '/framework/class.admin.php';
$language = defined('LANGUAGE') ? strtoupper((string) LANGUAGE) : 'EN';
require __DIR__ . '/languages/EN.php';
$fallback = $CMC_LANG;
if ($language !== 'EN' && preg_match('/^[A-Z]{2}$/', $language) && is_readable(__DIR__ . '/languages/' . $language . '.php')) require __DIR__ . '/languages/' . $language . '.php';
$CMC_LANG = array_merge($fallback, isset($CMC_LANG) && is_array($CMC_LANG) ? $CMC_LANG : array());
$admin = new admin('admintools', 'admintools', false);
if (ob_get_level()) ob_end_clean();
if (!$admin->is_authenticated() || !$admin->get_permission('CodeMirror_Config', 'module')) cmc_reply(false, $CMC_LANG['forbidden'], 403);
if ((isset($_SERVER['REQUEST_METHOD'])&&is_string($_SERVER['REQUEST_METHOD'])?$_SERVER['REQUEST_METHOD']:'GET') !== 'POST' || !$admin->checkFTAN()) cmc_reply(false, $CMC_LANG['security'], 403, array('ftan' => $admin->getFTAN()));
$themes = array_map(function ($file) { return pathinfo($file, PATHINFO_FILENAME); }, list_files_from_dir(__DIR__ . '/codemirror/theme', 'css'));
$fonts = array_map(function ($file) { return pathinfo($file, PATHINFO_FILENAME); }, list_files_from_dir(__DIR__ . '/codemirror/fonts', array('woff2', 'woff')));
$theme = isset($_POST['theme'])&&is_scalar($_POST['theme']) ? (string) $_POST['theme'] : '';
$font = isset($_POST['font'])&&is_scalar($_POST['font']) ? (string) $_POST['font'] : '';
$fontSize = isset($_POST['font_size'])&&is_scalar($_POST['font_size']) ? (int) $_POST['font_size'] : 0;
if (!in_array($theme, $themes, true) || !in_array($font, $fonts, true) || !in_array($fontSize, array(12,13,14,15,16,17,18), true)) cmc_reply(false, $CMC_LANG['invalid'], 422, array('ftan' => $admin->getFTAN()));
$configData = array('theme' => $theme, 'font' => $font, 'font_size' => $fontSize);
$saved = Settings::Set('cmc_cfg', serialize($configData));
if ($saved !== false && $saved !== null && $saved !== '') cmc_reply(false, $CMC_LANG['save_failed'], 500, array('ftan' => $admin->getFTAN()));
$stored = @unserialize((string) Settings::Get('cmc_cfg', ''), array('allowed_classes' => false));
if (!is_array($stored)) $stored = $configData;
cmc_reply(true, $CMC_LANG['saved'], 200, array('config' => $stored, 'ftan' => $admin->getFTAN()));
