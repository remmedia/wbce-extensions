<?php
defined('WB_PATH') or die('No direct access');
$CAPTCHA_PROVIDER = array();
require __DIR__.'/languages/EN.php';
$captchaAltchaLanguage = defined('LANGUAGE') ? preg_replace('/[^A-Z]/', '', strtoupper((string)LANGUAGE)) : 'EN';
if ($captchaAltchaLanguage !== 'EN' && is_file(__DIR__.'/languages/'.$captchaAltchaLanguage.'.php')) {
    $captchaAltchaEnglish = $CAPTCHA_PROVIDER;
    require __DIR__.'/languages/'.$captchaAltchaLanguage.'.php';
    $CAPTCHA_PROVIDER = array_merge($captchaAltchaEnglish, $CAPTCHA_PROVIDER);
}
$captchaAltchaNativeHooks = (function_exists('wbce_add_action') && function_exists('wbce_add_filter')) || (defined('WBCE_VERSION') && version_compare((string)WBCE_VERSION, '1.7.0', '>='));
$captchaAltchaBridgeRow = null;
if (!$captchaAltchaNativeHooks && isset($database) && is_object($database) && is_callable([$database, 'query'])) {
    $captchaAltchaBridgeResult = $database->query("SELECT `version` FROM `{TP}addons` WHERE `type`='module' AND `directory`='wbce_hook_bridge' LIMIT 1");
    if (is_object($captchaAltchaBridgeResult) && is_callable([$captchaAltchaBridgeResult, 'fetchRow'])) {
        $captchaAltchaBridgeRow = $captchaAltchaBridgeResult->fetchRow(defined('MYSQLI_ASSOC') ? MYSQLI_ASSOC : 1);
    } elseif (is_object($captchaAltchaBridgeResult) && is_callable([$captchaAltchaBridgeResult, 'fetch_assoc'])) {
        $captchaAltchaBridgeRow = $captchaAltchaBridgeResult->fetch_assoc();
    }
}
$captchaAltchaHooksAvailable = $captchaAltchaNativeHooks || (is_array($captchaAltchaBridgeRow) && version_compare((string)($captchaAltchaBridgeRow['version'] ?? ''), '1.2.0', '>='));
$PRECHECK = array(
    'WBCE_VERSION' => array('VERSION' => '1.6.8', 'OPERATOR' => '>='),
    'PHP_VERSION' => array('VERSION' => '8.2.0', 'OPERATOR' => '>='),
    'WB_ADDONS' => array('captcha_control' => array('VERSION' => '3.1.33', 'OPERATOR' => '>=')),
    'CUSTOM_CHECKS' => array(
        $CAPTCHA_PRECHECK_LABEL => array(
            'REQUIRED' => $CAPTCHA_PROVIDER['hooks_required'],
            'ACTUAL' => $captchaAltchaHooksAvailable ? $CAPTCHA_PROVIDER['available'] : $CAPTCHA_PROVIDER['unavailable'],
            'STATUS' => $captchaAltchaHooksAvailable,
        ),
    ),
);
