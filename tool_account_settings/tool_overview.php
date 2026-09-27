<?php
/**
 * WBCE CMS AdminTool: Tool Account Settings
 * 
 * @platform    WBCE CMS 1.3.2 and higher
 * @package     modules/UserBase
 * @author      Christian M. Stefan <stefek@designthings.de>
 * @copyright   Christian M. Stefan
 * @license     see LICENSE.md of this package
 */
 
// prevent this file from being accessed directly
defined('WB_PATH') or exit("insufficient privileges" . __FILE__);

// check if user is allowed to use admin-tools (to prevent this file 
// to be called by an unauthorized user e.g. from a code-section)
if($admin->get_permission('admintools') == false) exit("insuficient privileges");

$sPluginsURL = get_url_from_path(__DIR__).'/js';

I::insertCssFile($sPluginsURL."/jquery.tablesorter/theme.wbEasy.css",                    "HEAD BTM-");
I::insertJsFile( $sPluginsURL."/jquery.tablesorter/jquery.tablesorter.js",               "HEAD BTM-");
I::insertJsFile( $sPluginsURL."/jquery.tablesorter/jquery.tablesorter.widgets.js",       "HEAD BTM-");
I::insertJsFile( $sPluginsURL."/jquery.tablesorter/tablesorter_accout_tool_settings.js", "BODY BTM-");
$sToCss = "
    .tablesorter thead .disabled {
        display:none;
    }
";
I::insertCssCode($sToCss, 'HEAD BTM-', 'tablesorter');

// WBCE 1.7 uses the canonical camelCase API. The underscored call remains
// solely as an isolated fallback for older WBCE 1.6.8 Accounts implementations.
$usesLegacyOverview = false;
if (method_exists($oAccounts, 'getUsersOverview')) {
    $aUsers = $oAccounts->getUsersOverview();
} elseif (method_exists($oAccounts, 'get_users_overview')) {
    $aUsers = $oAccounts->get_users_overview();
    $usesLegacyOverview = true;
} else {
    $aUsers = array();
}
if (!is_array($aUsers)) $aUsers = array();
$accountTimezone = null;
if (function_exists('wbce_timezone')) {
    try { $accountTimezone = wbce_timezone(); } catch (Throwable $exception) { $accountTimezone = null; }
}
if (!$accountTimezone instanceof DateTimeZone) {
    try { $accountTimezone = new DateTimeZone(date_default_timezone_get()); }
    catch (Throwable $exception) { $accountTimezone = new DateTimeZone('UTC'); }
}
if (!$accountTimezone instanceof DateTimeZone) {
    try { $accountTimezone = new DateTimeZone((string)$accountTimezone); }
    catch (Throwable $exception) { $accountTimezone = new DateTimeZone('UTC'); }
}
foreach ($aUsers as &$accountUser) {
    foreach (array('signup_timestamp', 'login_when') as $accountDateKey) {
        $rawDate = isset($accountUser[$accountDateKey]) ? $accountUser[$accountDateKey] : null;
        $accountUser[$accountDateKey.'_formatted'] = '';
        if ($rawDate !== null && $rawDate !== '' && (is_numeric($rawDate) || strtotime((string)$rawDate) !== false)) {
            $timestamp = is_numeric($rawDate) ? (int)$rawDate : (int)strtotime((string)$rawDate);
            // The isolated legacy-compatible Accounts API historically adds
            // the fixed TIMEZONE offset itself. Undo only that legacy behavior;
            // the DateTimeZone below then applies DST and the configured zone once.
            if ($usesLegacyOverview && is_numeric($rawDate) && defined('TIMEZONE')) {
                $timestamp -= (int)TIMEZONE;
            }
            $date = (new DateTimeImmutable('@'.$timestamp))->setTimezone($accountTimezone);
            $dateFormat = defined('DATE_FORMAT') && DATE_FORMAT !== '' ? DATE_FORMAT : 'd.m.Y';
            $timeFormat = defined('TIME_FORMAT') && TIME_FORMAT !== '' ? TIME_FORMAT : 'H:i';
            $accountUser[$accountDateKey.'_formatted'] = $date->format($accountDateKey === 'login_when' ? $dateFormat.' '.$timeFormat : $dateFormat);
        }
    }
}
unset($accountUser);
$field_row_framer_none = true;
$aToTwig = array(
    'TABS'                => $aTabs,
    'TOOL_NAME'           => $module_name,
    'TOOL_DESCRIPTION'    => $module_description,
    'CAN_MODIFY_ACCOUNTS' => in_array('users_modify', isset($_SESSION['SYSTEM_PERMISSIONS']) ? $_SESSION['SYSTEM_PERMISSIONS'] : array()),
    'CAN_CREATE_ACCOUNTS' => $admin->get_permission('users_add'),
    'CREATE_USER_URL'     => ADMIN_URL . '/users/index.php',
    'JS_ONCLICK'          => '',
    'USERLIST'            => $aUsers,
    'GET'                 => $_GET
);
$oTwig = getTwig(__DIR__ . '/theme/');
$oTemplate = $oTwig->load('tool_overview.twig');
$oTemplate->display($aToTwig);
