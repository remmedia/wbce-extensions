<?php
defined('WB_PATH') or die('No direct access');
foreach(array('log_center_url'=>'','log_center_endpoint'=>'','log_center_token'=>'','log_center_connection_enabled'=>true,'log_center_connection_ok'=>false,'log_center_connection_checked_at'=>0,'log_center_heartbeat_at'=>0,'log_center_error_enabled'=>true,'log_center_backup_enabled'=>true,'log_center_worker_enabled'=>true,'log_center_runtime_enabled'=>true,'log_center_update_enabled'=>true,'log_center_max_log_size_mb'=>3,'log_center_installed'=>true) as $name=>$value)if(class_exists('Settings'))Settings::Set($name,$value,false);
// One-time migration from the former Errorlogger-owned connection.
if(class_exists('Settings')&&Settings::GetDb('log_center_url','')===''){
    $legacyUrl=(string)Settings::GetDb('errorlogger_remote_url','');$legacyToken=(string)Settings::GetDb('errorlogger_remote_token','');
    if($legacyUrl!=='')Settings::Set('log_center_url',$legacyUrl);if($legacyToken!=='')Settings::Set('log_center_token',$legacyToken);
    if($legacyUrl!==''&&$legacyToken!==''&&filter_var(Settings::GetDb('errorlogger_remote_enabled',false),FILTER_VALIDATE_BOOLEAN))Settings::Set('log_center_error_enabled',true);
}

// Log rotation is owned by Log Center. Remove the legacy standalone
// Log Rotate task so it cannot remain in the scheduler after migration.
if (isset($database) && is_object($database) && method_exists($database, 'query')) {
    $hasTasks = !method_exists($database, 'table_exists') || $database->table_exists(TABLE_PREFIX . 'mod_worker_tasks');
    if ($hasTasks) {
        $database->query("DELETE FROM `{TP}mod_worker_tasks` WHERE `worker_id`='logrotate.rotate'");
    }
}
