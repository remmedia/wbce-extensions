<?php
defined('WB_PATH') or die('No direct access');

$CAPTCHA = array();
require __DIR__ . '/languages/EN.php';
$precheckLanguage = defined('LANGUAGE') ? preg_replace('/[^A-Z]/', '', strtoupper((string) LANGUAGE)) : 'EN';
$precheckLanguageFile = __DIR__ . '/languages/' . $precheckLanguage . '.php';
if ($precheckLanguage !== 'EN' && is_file($precheckLanguageFile)) {
    $englishCaptcha = $CAPTCHA;
    require $precheckLanguageFile;
    $CAPTCHA = array_merge($englishCaptcha, $CAPTCHA);
}

$nativeHooks = (function_exists('wbce_add_action') && function_exists('wbce_add_filter')) || (defined('WBCE_VERSION') && version_compare((string) WBCE_VERSION, '1.7.0', '>='));
$bridgeResult = !$nativeHooks && isset($database) && is_object($database) && is_callable([$database, 'query'])
    ? $database->query("SELECT `version` FROM `{TP}addons` WHERE `type`='module' AND `directory`='wbce_hook_bridge' LIMIT 1")
    : null;
$bridgeRow = null;
if (is_object($bridgeResult) && is_callable([$bridgeResult, 'fetchRow'])) {
    $bridgeRow = $bridgeResult->fetchRow(defined('MYSQLI_ASSOC') ? MYSQLI_ASSOC : 1);
} elseif (is_object($bridgeResult) && is_callable([$bridgeResult, 'fetch_assoc'])) {
    $bridgeRow = $bridgeResult->fetch_assoc();
}
$hooksAvailable = $nativeHooks || ($bridgeRow && version_compare((string) $bridgeRow['version'], '1.2.0', '>='));

$PRECHECK = array(
    'WBCE_VERSION' => array('VERSION' => '1.6.8', 'OPERATOR' => '>='),
    'PHP_VERSION' => array('VERSION' => '8.2.0', 'OPERATOR' => '>='),
    'CUSTOM_CHECKS' => array(
        $CAPTCHA['PRECHECK_HOOKS_LABEL'] => array(
            'REQUIRED' => $CAPTCHA['PRECHECK_HOOKS_REQUIRED'],
            'ACTUAL' => $hooksAvailable ? $CAPTCHA['PRECHECK_AVAILABLE'] : $CAPTCHA['PRECHECK_UNAVAILABLE'],
            'STATUS' => $hooksAvailable,
        ),
    ),
);
