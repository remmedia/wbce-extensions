<?php
/**
 * Version-specific update log. A newly prepared update replaces only the log
 * for its own target version, while logs for older releases stay available.
 */
defined('WB_PATH') or die('No direct access');

function updater_update_log_version(string $version): string
{
    $version = trim($version);
    $version = preg_replace('/[^0-9A-Za-z._-]+/', '-', $version) ?? '';
    return trim($version, '.-');
}

function updater_update_log_path(string $version): string
{
    $safe = updater_update_log_version($version);
    if ($safe === '') return '';
    return WB_PATH . '/var/logs/update-' . $safe . '.log';
}

function updater_update_log_start(string $version, string $source): void
{
    $path = updater_update_log_path($version);
    if ($path === '') return;
    $directory = dirname($path);
    if (!is_dir($directory)) @mkdir($directory, 0750, true);
    if (@file_put_contents($path, '', LOCK_EX) !== false) @chmod($path, 0640);
    updater_update_log($version, 'Information', 'Updater', 'Update auf ' . trim($version) . ' wurde vorbereitet (' . $source . ').');
}

function updater_update_log(string $version, string $severity, string $source, string $message, array $context = array()): void
{
    $path = updater_update_log_path($version);
    if ($path === '') return;
    $directory = dirname($path);
    if (!is_dir($directory)) @mkdir($directory, 0750, true);
    $entry = array(
        'time' => date('c'),
        'severity' => $severity,
        'source' => $source,
        'message' => $message,
    );
    if ($context) $entry['context'] = $context;
    $encoded = json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    if (is_string($encoded)) @file_put_contents($path, $encoded . "\n", FILE_APPEND | LOCK_EX);

    // Remote delivery is deliberately best-effort. A logging outage must never
    // delay or interrupt an update.
    $client = WB_PATH . '/modules/log_center/Client.php';
    if (is_file($client)) {
        try {
            require_once $client;
            if (class_exists('WbceLogCenterClient')) {
                WbceLogCenterClient::sendEvent('update', $severity, $source, $message);
            }
        } catch (Throwable $ignored) {
        }
    }
}
