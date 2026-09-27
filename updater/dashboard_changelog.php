<?php
/** Persist dismissal of the updater release-note widget. */
require dirname(__DIR__, 2) . '/config.php';
require_once WB_PATH . '/framework/Admin.php';

$admin = new Admin('Admintools', 'admintools', false, false);
$isAsync = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST'
    || (string) ($_POST['dashboard_action'] ?? '') !== 'dismiss_update_changelog'
    || !$admin->is_authenticated() || !$admin->checkFTAN()) {
    http_response_code(403);
    if ($isAsync) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array('ok' => false));
    }
    exit;
}

$version = trim((string) Settings::GetDb('wbce_update_changelog_version', ''));
if ($version !== '') {
    Settings::Set('wbce_update_changelog_dismissed_version', $version);
}
unset($_SESSION['WBCE_UPDATE_CHANGELOG']);
if ($isAsync) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array('ok' => true));
    exit;
}
header('Location: ' . ADMIN_URL . '/start/index.php');
exit;
