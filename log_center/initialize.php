<?php
defined('WB_PATH') or die('No direct access');
require_once __DIR__.'/Heartbeat.php';
require_once __DIR__.'/QueueWorker.php';
require_once __DIR__.'/LogMaintenance.php';
$workerRegistry=WB_PATH.'/modules/worker/Registry.php';
$workerAvailable=is_file($workerRegistry)&&(!function_exists('wbce_addon_is_active')||wbce_addon_is_active('worker','module'));
if(!class_exists('WbceWorkerRegistry')&&$workerAvailable)require_once $workerRegistry;
if(class_exists('WbceWorkerRegistry')){
    WbceWorkerRegistry::register('log_center.heartbeat',array('label'=>log_center_text('HEARTBEAT_LABEL'),'description'=>log_center_text('HEARTBEAT_DESCRIPTION'),'managed_task'=>true,'show_tasks'=>false,'default_name'=>log_center_text('HEARTBEAT_LABEL'),'default_cron'=>'*/4 * * * *','default_configuration'=>array(),'callable'=>array('WbceLogCenterHeartbeat','run')));
    WbceWorkerRegistry::register('log_center.delivery',array('label'=>log_center_text('QUEUE_WORKER_LABEL'),'description'=>log_center_text('QUEUE_WORKER_DESCRIPTION'),'managed_task'=>true,'show_tasks'=>false,'default_name'=>log_center_text('QUEUE_WORKER_LABEL'),'default_cron'=>'* * * * *','default_configuration'=>array(),'callable'=>array('WbceLogCenterQueueWorker','run')));
    WbceWorkerRegistry::register('log_center.rotate',array('label'=>log_center_text('ROTATION_LABEL'),'description'=>log_center_text('ROTATION_DESCRIPTION'),'managed_task'=>true,'show_tasks'=>false,'default_name'=>log_center_text('ROTATION_LABEL'),'default_cron'=>'*/5 * * * *','default_configuration'=>array(),'callable'=>array('WbceLogCenterMaintenance','run')));
}elseif(function_exists('fastcgi_finish_request')&&filter_var(Settings::GetDb('log_center_connection_enabled',true),FILTER_VALIDATE_BOOLEAN)&&((int)Settings::GetDb('log_center_heartbeat_at',0)<time()-240)){
    Settings::Set('log_center_heartbeat_at',time());
    register_shutdown_function(static function(){
        if(session_status()===PHP_SESSION_ACTIVE)session_write_close();
        if(!fastcgi_finish_request())return;
        try{WbceLogCenterMaintenance::run();}catch(Throwable $ignored){}
        try{WbceLogCenterHeartbeat::run();}catch(Throwable $ignored){}
    });
}
