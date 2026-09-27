<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
// CLI cron invocations have no HTTP request context. Supplying harmless local
// values keeps legacy pre-init/errorlogger modules from reading missing keys.
$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';
$_SERVER['REQUEST_URI'] = $_SERVER['REQUEST_URI'] ?? '/modules/worker/cron.php';
require dirname(__DIR__, 2) . '/config.php';
require_once __DIR__.'/Settings.php';
if (WbceWorkerSettings::mode($database) !== 'cron') { exit(0); }
$triggerLogId = WbceWorkerSettings::beginTrigger($database, 'cron');
require_once __DIR__.'/Dispatcher.php';
$result = WbceWorkerDispatcher::dispatch($database);
WbceWorkerSettings::finishTrigger($database, $triggerLogId, $result);
foreach ($result['errors'] as $error) { error_log('WBCE Worker '.$error); }
