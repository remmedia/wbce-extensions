<?php
defined('WB_PATH') or die('No direct access');
if(class_exists('Settings'))foreach(array('log_center_url','log_center_endpoint','log_center_token','log_center_connection_enabled','log_center_connection_ok','log_center_connection_checked_at','log_center_heartbeat_at','log_center_error_enabled','log_center_backup_enabled','log_center_worker_enabled','log_center_runtime_enabled','log_center_update_enabled','log_center_max_log_size_mb','log_center_installed') as $name)Settings::Delete($name);
