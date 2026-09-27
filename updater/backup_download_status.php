<?php
require '../../config.php';
require_once WB_PATH.'/framework/Admin.php';
require_once __DIR__.'/update_transaction.php';
$admin=new admin('Admintools','admintools',false,false);
header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');
if(!$admin->is_authenticated()||!$admin->isAdmin()){http_response_code(403);exit;}
$token=isset($_GET['token'])&&is_string($_GET['token'])?$_GET['token']:'';
$state=updater_backup_download_state_get($token);
$valid=is_array($state)&&!empty($state['expires'])&&(int)$state['expires']>=time()&&hash_equals((string)($state['token']??''),$token);
echo json_encode(array('complete'=>$valid&&!empty($state['complete']),'aborted'=>$valid&&!empty($state['aborted'])));
