<?php
/**
 * Final updater action after a successful CMS database update.
 *
 * This endpoint deliberately does not depend on the administrator session: a
 * CMS update may replace its session implementation. The one-time recovery
 * token is an unguessable secret generated for the running update and is only
 * accepted while its short-lived server-side snapshot and updater marker exist.
 */
declare(strict_types=1);

$configFile = dirname(__DIR__, 2) . '/config.php';
if (!is_file($configFile)) {
    http_response_code(500);
    exit;
}
require $configFile;
$updateLogFile = __DIR__ . '/UpdateLog.php';
if (is_file($updateLogFile)) require_once $updateLogFile;
require_once __DIR__ . '/update_transaction.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(array('ok' => false, 'message' => 'Ungültige Anfrage.'));
    exit;
}
$token = is_scalar($_POST['updater_session_recovery'] ?? null) ? (string)$_POST['updater_session_recovery'] : '';
$expectedVersion = is_scalar($_POST['expected_version'] ?? null) ? trim((string)$_POST['expected_version']) : '';
if ($expectedVersion !== '' && !preg_match('/^v?\d+\.\d+(?:\.\d+)?(?:[-+][0-9A-Za-z][0-9A-Za-z.-]*)?$/', $expectedVersion)) {
    http_response_code(400);
    echo json_encode(array('ok' => false, 'message' => 'Ungültige Zielversion.'));
    exit;
}
if (!preg_match('/^[a-f0-9]{64}$/i', $token)) {
    http_response_code(403);
    echo json_encode(array('ok' => false, 'message' => 'Ungültige Update-Freigabe.'));
    exit;
}

$recovery = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'wbce-updater-session-' . $token . '.json';
$marker = WB_PATH . '/temp/.wbce-updater-maintenance';
$recoveryData = json_decode((string)@file_get_contents($recovery), true);
$rollbackToken = is_array($recoveryData) && is_string($recoveryData['rollback_token'] ?? null) ? $recoveryData['rollback_token'] : '';
// The recovery snapshot is created immediately before extraction. Never allow
// a stale marker or an old token to change maintenance state later.
if (!is_file($recovery) || (time() - (int)@filemtime($recovery)) > 1800) {
    http_response_code(403);
    echo json_encode(array('ok' => false, 'message' => 'Die Update-Freigabe ist abgelaufen.'));
    exit;
}

// A 200 response from install/update.php is not itself proof of a completed
// database update: the legacy installer can end early with a rendered warning.
// Verify the version read from the files now on disk before reporting success.
$installedVersion = defined('NEW_WBCE_VERSION') ? (string)NEW_WBCE_VERSION : (defined('WBCE_VERSION') ? (string)WBCE_VERSION : '');
if ($expectedVersion !== '' && $installedVersion !== '' && version_compare(ltrim($installedVersion, 'v'), ltrim($expectedVersion, 'v'), '!=')) {
    if (function_exists('updater_update_log')) updater_update_log($expectedVersion, 'Error', 'Datenbank-Update', 'Datenbank-Update nicht abgeschlossen: installiert ist ' . $installedVersion . ', erwartet wurde ' . $expectedVersion . '.');
    http_response_code(409);
    echo json_encode(array(
        'ok' => false,
        'installed_version' => $installedVersion,
        'message' => 'Das Datenbank-Update wurde nicht abgeschlossen. Installiert ist weiterhin ' . $installedVersion . ', erwartet wurde ' . $expectedVersion . '.'
    ));
    exit;
}

// The visible updater version comes from the database. Confirm that the
// installer persisted the same release marker before committing rollback data.
$storedVersion = '';
try {
    require_once WB_PATH . '/framework/Database.php';
    $versionDb = new Database();
    $versionTable = preg_replace('/[^A-Za-z0-9_]/', '', (string)TABLE_PREFIX) . 'settings';
    $storedVersion = (string)$versionDb->fetchValue('SELECT `value` FROM `'.$versionTable.'` WHERE `name` = ? LIMIT 1', array('wbce_version'));
} catch (Throwable $error) {
    http_response_code(500);
    echo json_encode(array('ok' => false, 'message' => 'Die gespeicherte CMS-Version konnte nicht geprüft werden: '.$error->getMessage()));
    exit;
}
if ($expectedVersion !== '' && version_compare(ltrim($storedVersion, 'v'), ltrim($expectedVersion, 'v'), '!=')) {
    // A few legacy update scripts finish their schema work but leave the
    // release marker untouched.  The package version was already verified
    // above, so repair only this marker and prove the write before success.
    try {
        $writeSetting = static function ($name, $value) use ($versionDb, $versionTable): void {
            $versionDb->query('UPDATE `'.$versionTable.'` SET `value` = ? WHERE `name` = ?', array($value, $name));
            if ($versionDb->hasError()) throw new RuntimeException($versionDb->getError());
            $saved = (string)$versionDb->fetchValue('SELECT `value` FROM `'.$versionTable.'` WHERE `name` = ? LIMIT 1', array($name));
            if ($saved === (string)$value) return;
            $versionDb->insertRow($versionTable, array('name'=>$name, 'value'=>$value));
            if ($versionDb->hasError()) throw new RuntimeException($versionDb->getError());
            $saved = (string)$versionDb->fetchValue('SELECT `value` FROM `'.$versionTable.'` WHERE `name` = ? LIMIT 1', array($name));
            if ($saved !== (string)$value) throw new RuntimeException('Wert wurde nicht übernommen.');
        };
        $writeSetting('wbce_version', $expectedVersion);
        $writeSetting('wbce_tag', defined('NEW_WBCE_TAG') ? (string)NEW_WBCE_TAG : $expectedVersion);
        $storedVersion = (string)$versionDb->fetchValue('SELECT `value` FROM `'.$versionTable.'` WHERE `name` = ? LIMIT 1', array('wbce_version'));
        if (function_exists('updater_update_log')) updater_update_log($expectedVersion, 'Warning', 'Datenbank-Update', 'CMS-Versionskennung nach dem Updateabschluss bestätigt und korrigiert.');
    } catch (Throwable $error) {
        if (function_exists('updater_update_log')) updater_update_log($expectedVersion, 'Error', 'Datenbank-Update', 'CMS-Version konnte nicht korrigiert werden: '.$error->getMessage());
        http_response_code(409);
        echo json_encode(array('ok' => false, 'installed_version' => $installedVersion, 'stored_version' => $storedVersion, 'message' => 'Die CMS-Version wurde nicht in der Datenbank gespeichert und konnte nicht korrigiert werden: '.$error->getMessage()));
        exit;
    }
}
if ($expectedVersion !== '' && version_compare(ltrim($storedVersion, 'v'), ltrim($expectedVersion, 'v'), '!=')) {
    http_response_code(409);
    echo json_encode(array('ok' => false, 'installed_version' => $installedVersion, 'stored_version' => $storedVersion, 'message' => 'Die CMS-Version wurde nicht in der Datenbank bestätigt. Installiert ist '.$storedVersion.', erwartet wurde '.$expectedVersion.'.'));
    exit;
}
if ($rollbackToken !== '') updater_rollback_commit($rollbackToken);

if ($installedVersion === '') {
    http_response_code(500);
    echo json_encode(array('ok' => false, 'message' => 'Die installierte CMS-Version konnte nicht geprüft werden.'));
    exit;
}

if (!is_file($marker)) {
    if (function_exists('updater_update_log')) updater_update_log($expectedVersion !== '' ? $expectedVersion : $installedVersion, 'Success', 'Datenbank-Update', 'CMS-Datenbank-Update erfolgreich abgeschlossen.');
    echo json_encode(array('ok' => true, 'disabled' => false, 'installed_version' => $installedVersion));
    exit;
}

require_once __DIR__ . '/maintenance_helper.php';
try {
    $disabled = updater_disable_own_maintenance();
    if (!$disabled && is_file($marker)) {
        throw new RuntimeException('Der Wartungsmodus konnte nicht deaktiviert werden.');
    }
    if (function_exists('updater_update_log')) updater_update_log($expectedVersion !== '' ? $expectedVersion : $installedVersion, 'Success', 'Datenbank-Update', 'CMS-Datenbank-Update erfolgreich abgeschlossen.');
    echo json_encode(array('ok' => true, 'disabled' => $disabled, 'installed_version' => $installedVersion));
} catch (Throwable $exception) {
    if (function_exists('updater_update_log')) updater_update_log($expectedVersion !== '' ? $expectedVersion : $installedVersion, 'Error', 'Datenbank-Update', $exception->getMessage());
    http_response_code(500);
    echo json_encode(array('ok' => false, 'message' => $exception->getMessage()));
}
