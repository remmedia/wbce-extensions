<?php
if(!defined('WB_PATH'))require_once dirname(__DIR__,2).'/config.php';
require_once __DIR__.'/Registry.php';
$requested=isset($_GET['api'])?(string)$_GET['api']:'';$path=isset($_GET['path'])?(string)$_GET['path']:'';
if($requested===''&&isset($_SERVER['REQUEST_URI'])){$uriPath=(string)parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);$prefix=rtrim((string)parse_url(WB_URL,PHP_URL_PATH),'/').'/modules/api/';if(strpos($uriPath,$prefix)===0){$relative=trim(substr($uriPath,strlen($prefix)),'/');$parts=$relative===''?array():explode('/',$relative,2);$requested=$parts[0]??'';$path=$parts[1]??'';}}
if(!preg_match('/^[a-z][a-z0-9_-]{0,63}$/',$requested)){http_response_code(404);header('Content-Type: application/json; charset=UTF-8');header('Cache-Control: no-store');header('X-Content-Type-Options: nosniff');$json=json_encode(array('error'=>'api_not_found','message'=>wbce_api_t('api_not_found')),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);echo is_string($json)?$json:'{"error":"api_not_found"}';exit;}
$path=trim(str_replace('\\','/',rawurldecode((string)$path)),'/');
if(strpos($path,"\0")!==false||preg_match('~(?:^|/)\.\.?($|/)~',$path)){http_response_code(400);header('Content-Type: application/json; charset=UTF-8');header('Cache-Control: no-store');header('X-Content-Type-Options: nosniff');$json=json_encode(array('error'=>'invalid_api_path','message'=>wbce_api_t('invalid_path')),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);echo is_string($json)?$json:'{"error":"invalid_api_path"}';exit;}
WbceApiRegistry::dispatch($database,$requested,$path);
