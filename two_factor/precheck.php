<?php
defined('WB_PATH') or die('No direct access');
require_once __DIR__.'/Language.php';
$nativeHooks = (function_exists('wbce_add_action') && function_exists('wbce_add_filter'))
    || (defined('WBCE_VERSION') && version_compare((string) WBCE_VERSION, '1.7.0', '>='));
$bridgeResult = !$nativeHooks && isset($database) && is_object($database) && is_callable(array($database, 'query'))
    ? $database->query("SELECT `version` FROM `{TP}addons` WHERE `type`='module' AND `directory`='wbce_hook_bridge' LIMIT 1") : null;
$bridgeRow = null;
if (is_object($bridgeResult) && is_callable(array($bridgeResult, 'fetchRow'))) {
    $bridgeRow = $bridgeResult->fetchRow(defined('MYSQLI_ASSOC') ? MYSQLI_ASSOC : 1);
} elseif (is_object($bridgeResult) && is_callable(array($bridgeResult, 'fetch_assoc'))) {
    $bridgeRow = $bridgeResult->fetch_assoc();
}
$hooksAvailable = $nativeHooks || ($bridgeRow && version_compare((string) $bridgeRow['version'], '1.2.0', '>='));
$PRECHECK = array(
    'WBCE_VERSION' => array('VERSION' => '1.6.8', 'OPERATOR' => '>='),
    'PHP_VERSION' => array('VERSION' => '8.2.0', 'OPERATOR' => '>='),
    'CUSTOM_CHECKS' => array(wbce_two_factor_t('precheck_hooks_label') => array(
        'REQUIRED' => wbce_two_factor_t('precheck_hooks_required'),
        'ACTUAL' => $hooksAvailable ? wbce_two_factor_t('precheck_available') : wbce_two_factor_t('precheck_unavailable'),
        'STATUS' => $hooksAvailable,
    )),
);
