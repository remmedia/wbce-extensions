<?php
require_once '../../config.php';
if(!extension_loaded('gd')||!function_exists('imagepng')){http_response_code(503);exit;}
$rawToken=$_GET['token']??'';$token=is_scalar($rawToken)?(string)$rawToken:'';
if(!preg_match('/^[a-f0-9]{32}$/D',$token)||!isset($_SESSION['wbce_phpcapcha'][$token])||!is_array($_SESSION['wbce_phpcapcha'][$token])||(int)($_SESSION['wbce_phpcapcha'][$token]['expires']??0)<time()){http_response_code(404);exit;}
$answer=(string)($_SESSION['wbce_phpcapcha'][$token]['answer']??'');if($answer===''){http_response_code(404);exit;}
$image=imagecreatetruecolor(210,70);if($image===false){http_response_code(503);exit;}$background=imagecolorallocate($image,242,246,249);$ink=imagecolorallocate($image,35,55,75);$noise=imagecolorallocate($image,170,188,202);if($background===false||$ink===false||$noise===false){http_response_code(503);exit;}imagefill($image,0,0,$background);
for($i=0;$i<12;$i++)imageline($image,random_int(0,209),random_int(0,69),random_int(0,209),random_int(0,69),$noise);
$x=18;for($i=0;$i<strlen($answer);$i++){imagestring($image,5,$x,random_int(23,31),$answer[$i],$ink);$x+=29;}
header('Content-Type: image/png');header('X-Content-Type-Options: nosniff');header('Cache-Control: no-store, no-cache, must-revalidate');if(!imagepng($image))http_response_code(500);if(PHP_VERSION_ID<80500)imagedestroy($image);
