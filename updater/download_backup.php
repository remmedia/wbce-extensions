<?php
require '../../config.php';
require_once WB_PATH.'/framework/Admin.php';
require_once __DIR__.'/update_transaction.php';
$admin=new admin('Admintools','admintools',false,false);
if(!$admin->is_authenticated()||!$admin->isAdmin()){http_response_code(403);exit('Zugriff verweigert.');}
$token=isset($_GET['token'])&&is_string($_GET['token'])?$_GET['token']:'';
$file=isset($_GET['file'])&&is_string($_GET['file'])?basename($_GET['file']):'';
if($file===''||!preg_match('/^[\pL\pN._ -]+\.zip$/u',$file)){http_response_code(400);exit('Ungültige Sicherungsdatei.');}
$state=updater_backup_download_state_get($token);
if(!is_array($state)||empty($state['expires'])||(int)$state['expires']<time()||!hash_equals((string)($state['token']??''),$token)||!hash_equals((string)($state['file']??''),$file)){http_response_code(403);exit('Ungültiger Download.');}
$base=realpath(WB_PATH.(defined('BACKUP_DATA_DIR')?BACKUP_DATA_DIR:'/backups/'));
$path=$base?realpath($base.DIRECTORY_SEPARATOR.$file):false;
if($base===false||$path===false||strpos($path,$base.DIRECTORY_SEPARATOR)!==0||!is_file($path)||!is_readable($path)){http_response_code(404);exit('Sicherungsdatei nicht gefunden.');}
@set_time_limit(0);@ignore_user_abort(true);while(ob_get_level()>0)@ob_end_clean();
header('Content-Type: application/zip');header('Content-Disposition: attachment; filename="'.str_replace('"','',$file).'"');header('Content-Length: '.(string)filesize($path));header('Cache-Control: private, no-store');header('X-Content-Type-Options: nosniff');
$handle=fopen($path,'rb');$aborted=false;
while(is_resource($handle)&&!feof($handle)){$chunk=fread($handle,4*1024*1024);if($chunk===false)break;echo $chunk;flush();if(connection_aborted()){$aborted=true;break;}}
if(is_resource($handle))fclose($handle);
$state['complete']=!$aborted;$state['aborted']=$aborted;
updater_backup_download_state_set($state);
session_write_close();
exit;
