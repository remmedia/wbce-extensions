<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';
$_SERVER['REQUEST_URI'] = $_SERVER['REQUEST_URI'] ?? '/modules/worker/run.php';
require dirname(__DIR__, 2) . '/config.php';
require_once __DIR__ . '/Service.php';
$options = getopt('', array('task:', 'token:'));
$taskId = isset($options['task']) ? (int)$options['task'] : 0;
$token = isset($options['token']) ? (string)$options['token'] : '';
if ($taskId < 1 || !preg_match('/^[a-f0-9]{64}$/', $token)) { fwrite(STDERR, worker_t('invalid_arguments')."\n"); exit(2); }
try { (new WbceWorkerService($database))->execute($taskId, $token); }
catch (Throwable $exception) { error_log('WBCE Worker: ' . $exception->getMessage()); exit(1); }
