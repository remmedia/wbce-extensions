<?php
require '../../config.php';
require_once WB_PATH.'/framework/Admin.php';
require_once __DIR__.'/update_transaction.php';
$admin=new admin('Admintools','admintools',false,false);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
if(!$admin->is_authenticated()||!$admin->isAdmin()){http_response_code(403);exit;}
$dir=WB_PATH.(defined('BACKUP_DATA_DIR')?BACKUP_DATA_DIR:'/backups/');
$files=array_filter(glob(rtrim($dir,'/').'/*.zip')?:array(),function($file){return is_file($file)&&filesize($file)>=102400;});
if(!$files){echo json_encode(array('ok'=>false));exit;}
usort($files,function($a,$b){return filemtime($b)<=>filemtime($a);});
$file=$files[0];
try{$token=bin2hex(random_bytes(24));}catch(Throwable $ignored){$token=sha1(uniqid('',true));}
updater_backup_download_state_set(array('token'=>$token,'file'=>basename($file),'complete'=>false,'aborted'=>false,'expires'=>time()+3600));
if(session_status()===PHP_SESSION_ACTIVE)session_write_close();
try{$tz=new DateTimeZone(defined('DEFAULT_TIMEZONE')?(string)DEFAULT_TIMEZONE:date_default_timezone_get());}catch(Throwable $ignored){$tz=new DateTimeZone('Europe/Berlin');}
$date=new DateTime('@'.filemtime($file));$date->setTimezone($tz);
echo json_encode(array('ok'=>true,'name'=>basename($file),'time'=>$date->format('d.m.Y H:i'),'size'=>round(filesize($file)/1048576,1),'url'=>WB_URL.'/modules/updater/download_backup.php?file='.rawurlencode(basename($file)).'&token='.rawurlencode($token)));
