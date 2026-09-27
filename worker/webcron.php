<?php
require dirname(__DIR__, 2).'/config.php';
require_once __DIR__.'/Settings.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
WbceWorkerSettings::install($database);
$provided = isset($_GET['token']) && is_scalar($_GET['token']) ? (string)$_GET['token'] : '';
$expected = WbceWorkerSettings::get($database, 'url_token', '');
if (WbceWorkerSettings::mode($database) !== 'url' || $expected === '' || !hash_equals($expected, $provided)) {
    http_response_code(403); echo json_encode(array('ok'=>false,'message'=>worker_t('invalid_token'))); exit;
}
$triggerLogId = WbceWorkerSettings::beginTrigger($database, 'url');
WbceWorkerSettings::updateTriggerStage($database,$triggerLogId,worker_t('trace_received'));

// A URL-cron service only needs an acknowledgement. On PHP-FPM, finish the
// public response before connecting to the individual task endpoints. This
// prevents long-running jobs and temporarily slow loopback connections from
// keeping the cron HTTP request (and a browser test of it) open.
$fastCgiDetached = function_exists('fastcgi_finish_request');
if ($fastCgiDetached) {
    ignore_user_abort(true);
    http_response_code(202);
    echo json_encode(array('ok'=>true, 'accepted'=>true), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    fastcgi_finish_request();
}
require_once __DIR__.'/Dispatcher.php';
$result = WbceWorkerDispatcher::dispatch($database,static function($stage)use($database,$triggerLogId){WbceWorkerSettings::updateTriggerStage($database,$triggerLogId,(string)$stage);},$fastCgiDetached);
WbceWorkerSettings::finishTrigger($database, $triggerLogId, $result);
if (!$fastCgiDetached) {
    echo json_encode(array('ok'=>empty($result['errors']), 'started'=>$result['started'], 'errors'=>$result['errors']), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
}
