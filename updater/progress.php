<?php
declare(strict_types=1);
$configFile = dirname(dirname(dirname(__FILE__))) . '/config.php';
if (!is_file($configFile)) { http_response_code(500); exit; }
require $configFile;
require_once WB_PATH . '/framework/Admin.php';
$admin = new admin('Admintools', 'admintools', false, false);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
if (!$admin->is_authenticated() || !$admin->isAdmin() || ($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { http_response_code(403); echo json_encode(array('ok'=>false)); exit; }
$token = is_scalar($_POST['update_token'] ?? null) ? (string)$_POST['update_token'] : '';
$after = max(0, (int)($_POST['after'] ?? 0));
if (!preg_match('/^[a-f0-9]{32,128}$/i', $token)) { echo json_encode(array('ok'=>false,'events'=>array())); exit; }
$file = WB_PATH . '/temp/.wbce-updater-progress-' . hash('sha256', $token) . '.ndjson';
$events = array();
if (is_readable($file)) foreach ((array)@file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    $event = json_decode($line, true);
    if (is_array($event) && (int)($event['id'] ?? 0) > $after) $events[] = $event;
}
echo json_encode(array('ok'=>true,'events'=>array_slice($events, -1000)), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
