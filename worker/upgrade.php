<?php
if (!defined('WB_PATH')) { die('Access denied'); }
require __DIR__ . '/install.php';
$database->query("UPDATE `{TP}addons` SET `name`='".$database->escapeString(worker_t('module_name'))."',`description`='".$database->escapeString(worker_t('module_description'))."' WHERE `type`='module' AND `directory`='worker'");

// Recalculate persisted next-run values once during the update. Older builds
// interpreted cron hours in the server timezone instead of the WBCE timezone.
require_once __DIR__.'/Service.php';
$workerUpgradeService = new WbceWorkerService($database);
foreach ($workerUpgradeService->tasks() as $workerUpgradeTask) {
    if (!empty($workerUpgradeTask['active']) && empty($workerUpgradeTask['lock_token'])) {
        $workerUpgradeService->toggle((int)$workerUpgradeTask['id'], true);
    }
}

// Remove the ambiguous endpoint name used briefly before webcron.php.
foreach (array('Trigger.php', 'trigger.php') as $obsoleteTrigger) {
    if (is_file(__DIR__.'/'.$obsoleteTrigger)) { @unlink(__DIR__.'/'.$obsoleteTrigger); }
}
