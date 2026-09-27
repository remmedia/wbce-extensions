<?php
/**
 * Updater dashboard integration.
 *
 * The CMS dashboard provides only the generic widget hook. All release-note
 * state, rendering and dismissal handling remains owned by this module.
 */
if (!defined('WB_PATH') || !function_exists('wbce_add_filter')) {
    return;
}

wbce_add_filter('admin.dashboard.widgets', static function (array $widgets, int $userId = 0, ?Admin $admin = null): array {
    $version = trim((string) Settings::GetDb('wbce_update_changelog_version', ''));
    $dismissedVersion = trim((string) Settings::GetDb('wbce_update_changelog_dismissed_version', ''));
    $file = basename((string) Settings::GetDb('wbce_update_changelog_file', ''));
    if ($version === '' || $version === $dismissedVersion || !preg_match('/^[0-9A-Za-z._-]+\.txt$/', $file)) {
        return $widgets;
    }

    $content = @file_get_contents(WB_PATH . '/var/update-changelog/' . $file);
    if (!is_string($content) || trim($content) === '') {
        return $widgets;
    }

    if ($admin === null) {
        return $widgets;
    }
    // Use the module controller within /admin.  This keeps the request in
    // the authenticated backend route, where global request filters preserve
    // the FTAN.
    $action = htmlspecialchars(ADMIN_URL . '/admintools/tool.php?tool=updater', ENT_QUOTES, 'UTF-8');
    $tokenField = $admin->getFTAN();
    $html = '<section class="wbce-dashboard-hook-widget wbce-card wbce-update-changelog">'
        . '<header><h3>Update - Changelog</h3>'
        . '<form method="post" action="' . $action . '" class="wbce-update-changelog-close">'
        . $tokenField . '<input type="hidden" name="dashboard_action" value="dismiss_update_changelog">'
        . '<button type="submit" aria-label="Changelog schließen">×</button></form></header>'
        . '<p>Das Update wurde abgeschlossen. Diese Hinweise bleiben sichtbar, bis Sie sie schließen.</p>'
        . '<pre>' . htmlspecialchars(substr($content, 0, 262144), ENT_QUOTES, 'UTF-8') . '</pre></section>';
    $widgets[] = array('html' => $html, 'priority' => 20);
    return $widgets;
});
