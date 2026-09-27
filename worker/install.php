<?php
if (!defined('WB_PATH')) { die('Access denied'); }
require_once __DIR__.'/DatabaseCompatibility.php';
require_once __DIR__.'/Language.php';
if (!is_object($database) || !method_exists($database, 'query')) {
  throw new RuntimeException(worker_t('database_unavailable'));
}

$workerActiveSql = method_exists($database, 'field_exists') && $database->field_exists('{TP}addons', 'active') ? ", `active`=1" : '';
$database->query("UPDATE `{TP}addons` SET `function`='tool,initialize'".$workerActiveSql." WHERE `type`='module' AND `directory`='worker'");

$database->query("CREATE TABLE IF NOT EXISTS `{TP}mod_worker_tasks` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `worker_id` varchar(190) NOT NULL,
  `cron_expression` varchar(190) NOT NULL DEFAULT '* * * * *',
  `configuration` text NOT NULL,
  `managed` tinyint(1) NOT NULL DEFAULT 0,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `next_run` datetime DEFAULT NULL,
  `last_run` datetime DEFAULT NULL,
  `last_status` varchar(20) DEFAULT NULL,
  `last_message` text DEFAULT NULL,
  `locked_at` datetime DEFAULT NULL,
  `lock_token` char(64) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `modified_at` datetime NOT NULL,
  PRIMARY KEY (`id`), KEY `due` (`active`,`next_run`), KEY `lock_token` (`lock_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$managedColumn = $database->query('SHOW COLUMNS FROM `{TP}mod_worker_tasks` LIKE \'managed\'');
$managedColumnRow = worker_db_row($managedColumn);
if (!$managedColumnRow) {
  $database->query('ALTER TABLE `{TP}mod_worker_tasks` ADD `managed` tinyint(1) NOT NULL DEFAULT 0 AFTER `configuration`');
}

$workerNow = gmdate('Y-m-d H:i:s');
$workerCleanupName = worker_t('cleanup_label');
$database->query("UPDATE `{TP}mod_worker_tasks` SET `managed`=1 WHERE `worker_id`='worker.cleanup_logs'");
$database->query("UPDATE `{TP}mod_worker_tasks` SET `managed`=1 WHERE `worker_id`='backup_center.create'");
$database->query("UPDATE `{TP}mod_worker_tasks` SET `managed`=1 WHERE `worker_id`='store.autoupdate'");
$database->query("INSERT INTO `{TP}mod_worker_tasks` (`name`,`worker_id`,`cron_expression`,`configuration`,`managed`,`active`,`next_run`,`created_at`,`modified_at`) SELECT '".$database->escapeString($workerCleanupName)."','worker.cleanup_logs','0 3 * * *','{\"days\":30}',1,1,'".$workerNow."','".$workerNow."','".$workerNow."' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `{TP}mod_worker_tasks` WHERE `worker_id`='worker.cleanup_logs' AND `managed`=1)");

$cronColumn = $database->query('SHOW COLUMNS FROM `{TP}mod_worker_tasks` LIKE \'cron_expression\'');
$cronColumnRow = worker_db_row($cronColumn);
if (!$cronColumnRow || !preg_match('/varchar\((\d+)\)/i', $cronColumnRow['Type'], $cronLength) || (int)$cronLength[1] < 190) {
  $database->query('ALTER TABLE `{TP}mod_worker_tasks` MODIFY `cron_expression` varchar(190) NOT NULL DEFAULT \'* * * * *\'');
}

$database->query("CREATE TABLE IF NOT EXISTS `{TP}mod_worker_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `task_id` int unsigned NOT NULL,
  `started_at` datetime NOT NULL,
  `finished_at` datetime DEFAULT NULL,
  `status` varchar(20) NOT NULL,
  `message` text DEFAULT NULL,
  `duration_ms` int unsigned DEFAULT NULL,
  PRIMARY KEY (`id`), KEY `task_started` (`task_id`,`started_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

require_once __DIR__.'/Settings.php';
WbceWorkerSettings::install($database);

// Older Worker releases did not persist the origin of a task. Migrate once
// with the safe default: existing tasks are considered module-created. Tasks
// subsequently created by the Worker UI explicitly receive managed=0.
$workerOriginMigrated = worker_db_value($database, "SELECT `value` FROM `{TP}mod_worker_settings` WHERE `name`='task_origin_migrated'");
if ((string)$workerOriginMigrated !== '1') {
  $database->query('UPDATE `{TP}mod_worker_tasks` SET `managed`=1');
  $database->query("INSERT INTO `{TP}mod_worker_settings` (`name`,`value`) VALUES ('task_origin_migrated','1') ON DUPLICATE KEY UPDATE `value`='1'");
}

// Modules may have been installed before Worker. Let each installed and active
// module register its own definitions, then create/update its managed tasks.
require_once __DIR__.'/initialize.php';
require_once __DIR__.'/ModuleDiscovery.php';
WbceWorkerModuleDiscovery::registerInstalledModules($database);
require_once __DIR__.'/Service.php';
new WbceWorkerService($database);
