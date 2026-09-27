<?php
/**
 * Updater - Maintenance Mode Helper
 *
 * @category    module
 * @package     updater
 * @version     1.0.5
 * @author      WBCE Community
 * @copyright   2026 WBCE Community
 * @license     MIT License
 */
defined('WB_PATH') or die("This file can't be accessed directly!");

/**
 * Notify the CMS hook layer when it is available.
 *
 * WBCE 1.6.8 does not provide the hook function itself. The updater must
 * remain usable there without making the optional hook bridge a hard
 * dependency.
 */
function updater_notify_hook(string $hook, array $context = []): void
{
    if (function_exists('wbce_do_action')) {
        wbce_do_action($hook, $context);
    }
}

/**
 * Activates WBCE maintenance mode via the Settings class.
 *
 * @param  array  $errors  Reference to the caller's error array — warnings are appended here.
 * @param  array  $LANG    Language strings array from the caller.
 * @return array           ['activated' => bool, 'already_active' => bool]
 */
function updater_enable_maintenance(array &$errors, array $LANG): array {
    $result = ['activated' => false, 'already_active' => false];

    try {
        require_once WB_PATH . '/framework/Settings.php';

        $currentStatus = (string)Settings::Get('wb_maintainance_mode');

        if ($currentStatus) {
            $result['already_active'] = true;
            $result['activated']      = true;
            return $result;
        }

        $template_paths = [
            WB_PATH . '/templates/systemplates/maintainance.tpl.php',
            WB_PATH . '/templates/' . DEFAULT_TEMPLATE . '/systemplates/maintainance.tpl.php',
        ];

        $template_exists = false;
        foreach ($template_paths as $tpl_path) {
            if (file_exists($tpl_path)) {
                $template_exists = true;
                break;
            }
        }

        if (!$template_exists) {
            $errors[] = $LANG['WARNING_NO_MAINTENANCE_TEMPLATE'];
        }

        Settings::Set('wb_maintainance_mode', '1');

        if ((string)Settings::Get('wb_maintainance_mode')) {
            $result['activated'] = true;
            // Only a mode activated by this updater may be disabled
            // automatically after the update. An existing administrator-set
            // maintenance mode must remain untouched.
            @file_put_contents(WB_PATH . '/temp/.wbce-updater-maintenance', (string)time(), LOCK_EX);
            updater_notify_hook('system.maintenance.started', array('source' => 'updater'));
        } else {
            throw new Exception('Maintenance mode setting could not be verified');
        }

    } catch (Exception $e) {
        $errors[] = $LANG['WARNING_MAINTENANCE_FAILED'] . ': ' . $e->getMessage();
    }

    return $result;
}

/** Disable maintenance mode only when this updater activated it. */
function updater_disable_own_maintenance(): bool {
    $marker = WB_PATH . '/temp/.wbce-updater-maintenance';
    if (!is_file($marker)) {
        return false;
    }
    require_once WB_PATH . '/framework/Settings.php';
    Settings::Set('wb_maintainance_mode', '0');
    if (!(string)Settings::Get('wb_maintainance_mode')) {
        @unlink($marker);
        updater_notify_hook('system.maintenance.completed', array('source' => 'updater'));
        return true;
    }
    return false;
}
