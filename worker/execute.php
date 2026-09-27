<?php
// Internal, authenticated endpoint used only to detach actual task execution
// from cron, URL-cron and page-view trigger requests.
define('WBCE_WORKER_INTERNAL_REQUEST', true);
ignore_user_abort(true);
@set_time_limit(0);
require dirname(__DIR__,2).'/config.php';
require_once __DIR__.'/Settings.php';
require_once __DIR__.'/Service.php';
$taskId=isset($_GET['task'])&&is_scalar($_GET['task'])?(int)$_GET['task']:0;
$token=isset($_GET['token'])&&is_scalar($_GET['token'])?(string)$_GET['token']:'';
$signature=isset($_GET['signature'])&&is_scalar($_GET['signature'])?(string)$_GET['signature']:'';
$secret=WbceWorkerSettings::get($database,'url_token','');$expected=$taskId>0&&preg_match('/^[a-f0-9]{64}$/',$token)?hash_hmac('sha256',$taskId.'|'.$token,$secret):'';
if($secret===''||$expected===''||!hash_equals($expected,$signature)){http_response_code(403);exit;}
header('Content-Type: text/plain; charset=UTF-8');header('Cache-Control: no-store');header('Connection: close');
echo "accepted\n";
if(function_exists('fastcgi_finish_request'))fastcgi_finish_request();else{while(ob_get_level()>0)@ob_end_flush();@flush();}
try{$service=new WbceWorkerService($database);$service->execute($taskId,$token);}catch(Throwable $exception){if(isset($service))try{$service->releaseFailedLaunch($taskId,$token,$exception->getMessage());}catch(Throwable $ignored){}error_log('WBCE Worker: '.$exception->getMessage());}
