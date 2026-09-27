<?php
// Internal CLI entry point for page-view cron execution. It is deliberately
// separate from the web endpoint so a page request can launch it without a
// DNS, TLS or loopback HTTP dependency.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
define('WBCE_WORKER_INTERNAL_REQUEST', true);
$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';
$_SERVER['REQUEST_URI'] = $_SERVER['REQUEST_URI'] ?? '/modules/worker/pageview-run.php';
require dirname(__DIR__, 2).'/config.php';
require_once __DIR__.'/Settings.php';
if (WbceWorkerSettings::mode($database) !== 'pageview') { exit(0); }

$triggerLogId = WbceWorkerSettings::beginTrigger($database, 'pageview');
require_once __DIR__.'/Dispatcher.php';
$result = WbceWorkerDispatcher::dispatch($database, null, true);
WbceWorkerSettings::finishTrigger($database, $triggerLogId, $result);
foreach ($result['errors'] as $error) { error_log('WBCE Worker '.$error); }
