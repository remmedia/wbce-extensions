<?php
defined('WB_PATH') or die('Access denied');
require_once __DIR__.'/Database.php';
$securityDatabase = wbce_security_center_database($database??null);
$securityWorkerTable=$securityDatabase->query("SHOW TABLES LIKE '".$securityDatabase->escapeString(TABLE_PREFIX.'mod_worker_tasks')."'");
if($securityWorkerTable&&$securityWorkerTable->fetchRow()){
    $securityDatabase->query("DELETE FROM `{TP}mod_worker_tasks` WHERE `worker_id` LIKE 'security_center.%'");
}
foreach (array(
    'mod_security_center_scan_queue',
    'mod_security_center_virus_queue',
    'mod_security_center_virus_findings',
    'mod_security_center_file_integrity',
    'mod_security_center_findings',
    'mod_security_center_events',
    'mod_security_center_settings',
    'mod_security_center_rules',
    'mod_security_center_visits',
    'mod_security_center_notifications',
    'mod_security_center_diagnostics',
    'mod_security_center_bot_cache',
    'mod_security_center_bans',
) as $securityTable) {
    $securityDatabase->query('DROP TABLE IF EXISTS `{TP}'.$securityTable.'`');
}
foreach (array(
    WB_PATH.'/temp/security-center-runtime.json',
    WB_PATH.'/temp/security-center-bots.log',
    WB_PATH.'/temp/security-center-traffic.log',
    WB_PATH.'/temp/security-center-traffic.log.processing',
    WB_PATH.'/temp/security-center-disable.flag',
) as $securityRuntimeFile) {
    if (is_file($securityRuntimeFile)) {
        @unlink($securityRuntimeFile);
    }
}
