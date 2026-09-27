<?php
require_once '../../config.php';
require_once WB_PATH . '/framework/class.admin.php';
require_once __DIR__ . '/Service.php';
require_once __DIR__ . '/AsyncLauncher.php';
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');
$admin = new admin('admintools', 'admintools', false, false);
if (!$admin->is_authenticated() || !$admin->get_permission('worker', 'module')) {
    http_response_code(403); echo json_encode(array('ok'=>false,'message'=>worker_t('permission_denied'))); exit;
}
$service = new WbceWorkerService($database);
$rawAction = $_POST['action'] ?? $_GET['action'] ?? 'list';
$action = is_scalar($rawAction) ? (string)$rawAction : '';
try {
    if ($action !== 'list' && (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST')) {
        throw new RuntimeException(worker_t('post_required'));
    }
    if ($action !== 'list' && (!$admin->checkFTAN())) { throw new RuntimeException(worker_t('security_expired')); }
    if ($action === 'save') { $service->save($_POST, true); }
    elseif ($action === 'toggle') { $service->toggle((int)($_POST['id'] ?? 0), !empty($_POST['active']), true); }
    elseif ($action === 'delete') { $service->delete((int)($_POST['id'] ?? 0), true); }
    elseif ($action === 'run') {
        $id = (int)($_POST['id'] ?? 0); $token = $service->claim($id, true, true);
        if ($token === null) { throw new RuntimeException(worker_t('already_running')); }
        try { WbceWorkerAsyncLauncher::launch($id, $token); }
        catch (Throwable $exception) { $service->releaseFailedLaunch($id, $token, $exception->getMessage()); throw $exception; }
    } elseif ($action !== 'list') { throw new InvalidArgumentException(worker_t('unknown_action')); }
    $definitions = array();
    foreach (WbceWorkerRegistry::all() as $id => $definition) {
        $definitions[$id] = array('label'=>$definition['label'], 'description'=>$definition['description'], 'default_cron'=>$definition['default_cron'], 'show_tasks'=>$definition['show_tasks'], 'show_process'=>$definition['show_process'], 'managed_task'=>$definition['managed_task']);
    }
    echo json_encode(array('ok'=>true,'tasks'=>$service->tasks(),'logs'=>$service->logs(1000),'triggers'=>$service->triggerLogs(100),'definitions'=>$definitions), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
} catch (Throwable $exception) {
    http_response_code(400); echo json_encode(array('ok'=>false,'message'=>$exception->getMessage()), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
}
