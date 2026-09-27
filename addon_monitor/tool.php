<?php
/**
 * AdminTool: addonMonitor
 *
 * This file provides some functions for the addonMonitor Tool.
 *
 * @package     addonMonitor
 * @author      Christian M. Stefan (Stefek)
 * @copyright   Christian M. Stefan
 * @license     http://www.gnu.org/licenses/gpl-2.0.html
 */
 
// Direct access prevention
defined('WB_PATH') or die(header('Location: ../index.php'));


if (!class_exists('admin', false)) {
    $admin_header = false;
    include(WB_PATH.'/framework/class.admin.php');
    $admin = new admin('admintools', 'admintools');
}
// check for permission
if (!$admin->get_permission('admintools')) {
    die(header('Location: ../../index.php'));
}

require_once((dirname(__FILE__)) . '/info.php');
// get functions file for this AdminTool
require_once((dirname(__FILE__)) . '/functions.php');

$sAddonDir = $module_directory;
$supportsActivation = defined('WBCE_VERSION') && version_compare(WBCE_VERSION, '1.7.0', '>=')
    && function_exists('wbce_set_addon_active');
$activationMessage = '';
$activationError = '';
$storedState = null;
$isAsyncRequest = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['addon_monitor_action'])) {
    if (!$supportsActivation) {
        $activationError = addon_monitor_t('activation_requires_wbce17');
    } elseif (!$admin->checkFTAN()) {
        $activationError = addon_monitor_t('security_failed');
    } else {
        try {
            $directory = isset($_POST['module_directory']) ? (string)$_POST['module_directory'] : '';
            if (!preg_match('/^[a-z0-9][a-z0-9_-]{0,79}$/i', $directory)) {
                throw new InvalidArgumentException(addon_monitor_t('invalid_directory'));
            }
            if ($directory === $module_directory && empty($_POST['enabled'])) {
                throw new RuntimeException(addon_monitor_t('self_disable'));
            }
            $registered = addon_monitor_db_value($database, "SELECT COUNT(*) FROM `{TP}addons` WHERE `type`='module' AND `directory`='".$database->escapeString($directory)."'");
            if ((int)$registered !== 1) {
                throw new RuntimeException(addon_monitor_t('module_not_found'));
            }
            wbce_set_addon_active($directory, !empty($_POST['enabled']), 'module');
            $storedState = addon_monitor_db_value($database, "SELECT `active` FROM `{TP}addons` WHERE `type`='module' AND `directory`='".$database->escapeString($directory)."'");
            if ((int)$storedState !== (!empty($_POST['enabled']) ? 1 : 0)) {
                throw new RuntimeException(addon_monitor_t('status_not_confirmed'));
            }
            $activationMessage = (int)$storedState === 1
                ? addon_monitor_t('activated')
                : addon_monitor_t('deactivated');
        } catch (Throwable $exception) {
            $activationError = $exception->getMessage();
        }
    }
    if ($isAsyncRequest) {
        header('Content-Type: application/json; charset=UTF-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        $success = $activationError === '';
        if (!$success) http_response_code(400);
        echo json_encode(array('success'=>$success,'message'=>$success?$activationMessage:$activationError,'enabled'=>$success?(int)$storedState===1:!empty($_POST['enabled']),'ftan'=>$admin->getFTAN()), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        exit;
    }
}

// Create Twig template object and configure it
$addonMonitorTwigOptions = array('autoescape'=>'html','cache'=>false,'strict_variables'=>false,'debug'=>false);
if (function_exists('getTwig')) {
    $oTwig = getTwig(dirname(__FILE__) . '/skel/');
} elseif (class_exists('Twig\\Loader\\FilesystemLoader') && class_exists('Twig\\Environment')) {
    $oTwigLoader = new \Twig\Loader\FilesystemLoader(dirname(__FILE__) . '/skel');
    $oTwig = new \Twig\Environment($oTwigLoader, $addonMonitorTwigOptions);
} elseif (class_exists('Twig_Loader_Filesystem') && class_exists('Twig_Environment')) {
    $oTwigLoader = new \Twig_Loader_Filesystem(dirname(__FILE__) . '/skel');
    $oTwig = new \Twig_Environment($oTwigLoader, $addonMonitorTwigOptions);
} else {
    throw new RuntimeException(addon_monitor_t('twig_unavailable'));
}
// SET SOME GLOBALS FOR USE ALONG WITH TWIG-TEMPLATES
$oTwig->addGlobal('WB_URL', WB_URL);
$oTwig->addGlobal('ICONS_DIR', '../../modules/'.$sAddonDir.'/icons');
$oTwig->addGlobal('ACTIVATION_SUPPORTED', $supportsActivation);
$oTwig->addGlobal('ACTIVATION_FTAN', $supportsActivation ? $admin->getFTAN() : '');
$oTwig->addGlobal('ACTIVATION_URL', WB_URL.'/modules/'.$module_directory.'/ajax.php');
$oTwig->addGlobal('ADDON_MONITOR_DIR', $module_directory);
$oTwig->addGlobal('T', addon_monitor_texts());

$aOuptut = array();
$sMonitorCase = isset($_GET['addons']) ? (string) $_GET['addons'] : 'modules';
if (!in_array($sMonitorCase, array('modules', 'templates', 'languages'), true)) {
    $sMonitorCase = 'modules';
}
$sActiveModules = '';
$sActiveTemplates = '';
$sActiveLanguages = '';
    switch ($sMonitorCase) {
        case 'templates':
            // frontend templates AND admin control panel (acp) themes
            $sActiveTemplates = 'current_tab';
            $aOuptut = getTemplatesArray();
        break;
        case 'languages':
            // languages
            $sActiveLanguages = 'current_tab';
            $aOuptut = getLanguagesArray();
        break;
        case 'modules':
            // page-type modules, admin-tools AND snippets
        default:
            $aOuptut = getModulesArray();
            $sActiveModules = 'current_tab';
        break;
    }
$oTemplate = $oTwig->load('monitor_' . $sMonitorCase . '.twig'); // load the template by name

$sToolUrl = ADMIN_URL.'/admintools/tool.php?tool='.$sAddonDir;
$addonMonitorTextsJson = json_encode(addon_monitor_texts(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
if (!is_string($addonMonitorTextsJson)) {
    $addonMonitorTextsJson = '{}';
}
?>
<div class="addon-monitor" data-texts="<?php echo htmlspecialchars($addonMonitorTextsJson, ENT_QUOTES, 'UTF-8');?>">
    <header class="addon-monitor-hero wbce-admin-header">
        <span class="addon-monitor-hero-icon"><i class="fa fa-eye" aria-hidden="true"></i></span>
        <div class="addon-monitor-hero-copy"><h2><?php echo htmlspecialchars(addon_monitor_t('module_name'), ENT_QUOTES, 'UTF-8');?> <span class="addon-monitor-version"><?php echo htmlspecialchars($module_version, ENT_QUOTES, 'UTF-8');?></span></h2>
        <p><?php echo htmlspecialchars(addon_monitor_t('intro'),ENT_QUOTES,'UTF-8');?></p></div>
        <div class="addon-monitor-hero-count"><strong><?php echo isset($aOuptut['addons']) && is_array($aOuptut['addons']) ? count($aOuptut['addons']) : 0;?></strong><span><?php echo htmlspecialchars(addon_monitor_t('entries'),ENT_QUOTES,'UTF-8');?></span></div>
    </header>
    <nav aria-label="<?php echo htmlspecialchars(addon_monitor_t('nav_label'),ENT_QUOTES,'UTF-8');?>">
        <ul class="addon-monitor-tabs">
            <li class="<?php echo $sActiveModules;?>"><a href="<?php echo htmlspecialchars($sToolUrl, ENT_QUOTES, 'UTF-8');?>&amp;addons=modules"><i class="fa fa-puzzle-piece"></i> <?php echo htmlspecialchars(addon_monitor_t('modules'),ENT_QUOTES,'UTF-8');?></a></li>
            <li class="<?php echo $sActiveTemplates;?>"><a href="<?php echo htmlspecialchars($sToolUrl, ENT_QUOTES, 'UTF-8');?>&amp;addons=templates"><i class="fa fa-paint-brush"></i> <?php echo htmlspecialchars(addon_monitor_t('templates'),ENT_QUOTES,'UTF-8');?></a></li>
            <li class="<?php echo $sActiveLanguages;?>"><a href="<?php echo htmlspecialchars($sToolUrl, ENT_QUOTES, 'UTF-8');?>&amp;addons=languages"><i class="fa fa-language"></i> <?php echo htmlspecialchars(addon_monitor_t('languages'),ENT_QUOTES,'UTF-8');?></a></li>
        </ul>
    </nav>
    <div class="addon-monitor-content">
        <?php if ($activationMessage !== ''): ?><p class="addon-monitor-message success"><?php echo htmlspecialchars($activationMessage, ENT_QUOTES, 'UTF-8');?></p><?php endif;?>
        <?php if ($activationError !== ''): ?><p class="addon-monitor-message error"><?php echo htmlspecialchars($activationError, ENT_QUOTES, 'UTF-8');?></p><?php endif;?>
        <div class="addon-monitor-list-heading"><div><h3><?php echo htmlspecialchars(addon_monitor_t($sMonitorCase === 'templates' ? 'installed_templates' : ($sMonitorCase === 'languages' ? 'installed_languages' : 'installed_modules')),ENT_QUOTES,'UTF-8');?></h3><p><?php echo htmlspecialchars(addon_monitor_t('list_help'),ENT_QUOTES,'UTF-8');?></p></div><span class="addon-monitor-result-count"><?php echo isset($aOuptut['addons']) && is_array($aOuptut['addons']) ? count($aOuptut['addons']) : 0;?> <?php echo htmlspecialchars(addon_monitor_t('found'),ENT_QUOTES,'UTF-8');?></span></div>
        <div class="addon-monitor-table addon-monitor-<?php echo htmlspecialchars($sMonitorCase, ENT_QUOTES, 'UTF-8');?>"><?php $oTemplate->display($aOuptut); ?></div>
    </div>
</div>
<noscript><div class="addon-monitor-noscript"><?php echo htmlspecialchars(addon_monitor_t('javascript_required'),ENT_QUOTES,'UTF-8');?></div></noscript>
