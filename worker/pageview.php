<?php
// Authenticated internal endpoint for non-blocking website-triggered runs on
// PHP environments where fastcgi_finish_request() is unavailable.
define('WBCE_WORKER_INTERNAL_REQUEST', true);
ignore_user_abort(true);
@set_time_limit(0);
require dirname(__DIR__, 2).'/config.php';
require_once __DIR__.'/Settings.php';

$timestamp = isset($_GET['timestamp']) && is_scalar($_GET['timestamp']) ? (int)$_GET['timestamp'] : 0;
$nonce = isset($_GET['nonce']) && is_scalar($_GET['nonce']) ? (string)$_GET['nonce'] : '';
$triggerLogId = isset($_GET['trigger']) && is_scalar($_GET['trigger']) ? (int)$_GET['trigger'] : 0;
$signature = isset($_GET['signature']) && is_scalar($_GET['signature']) ? (string)$_GET['signature'] : '';
$secret = WbceWorkerSettings::get($database, 'url_token', '');
$validNonce = preg_match('/^[a-f0-9]{32}$/', $nonce) === 1;
$expected = $secret !== '' && $validNonce
    ? hash_hmac('sha256', 'pageview|'.$timestamp.'|'.$nonce.'|'.$triggerLogId, $secret)
    : '';
if (WbceWorkerSettings::mode($database) !== 'pageview'
    || abs(time() - $timestamp) > 60
    || $triggerLogId < 1
    || $expected === ''
    || !hash_equals($expected, $signature)) {
    http_response_code(403);
    exit;
}

header('Content-Type: text/plain; charset=UTF-8');
header('Cache-Control: no-store');
header('Connection: close');
echo "accepted\n";
if (function_exists('fastcgi_finish_request')) {
    @fastcgi_finish_request();
} else {
    while (ob_get_level() > 0) { @ob_end_flush(); }
    @flush();
}

require_once __DIR__.'/Dispatcher.php';
$triggerResult = WbceWorkerDispatcher::dispatch($database, null, true);
WbceWorkerSettings::finishTrigger($database, $triggerLogId, $triggerResult);
