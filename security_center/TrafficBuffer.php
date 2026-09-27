<?php
require_once __DIR__.'/Language.php';

final class WbceSecurityCenterTrafficBuffer
{
    private static function text($key,array $replace=array()){return WbceSecurityCenterLanguage::text('worker_runtime.'.$key,$key,$replace);}

    public static function capture(array $settings)
    {
        if(self::isTechnicalRequest()||($settings['traffic_logging']??'0')!=='1')return;
        $rate=max(1,min(100,(int)($settings['traffic_sample_rate']??10)));
        try{if(random_int(1,100)>$rate)return;}catch(Throwable $exception){return;}
        $key=defined('DB_PASSWORD')?(string)DB_PASSWORD:__DIR__;
        $address=class_exists('WbceSecurityCenterFirewall',false)?WbceSecurityCenterFirewall::clientAddress($settings):(string)($_SERVER['REMOTE_ADDR']??'');
        $row=array('ip'=>hash_hmac('sha256',$address,$key),'path'=>substr((string)($_SERVER['REQUEST_URI']??''),0,500),'method'=>substr((string)($_SERVER['REQUEST_METHOD']??'GET'),0,10),'ua'=>substr((string)($_SERVER['HTTP_USER_AGENT']??''),0,500),'ref'=>substr((string)parse_url((string)($_SERVER['HTTP_REFERER']??''),PHP_URL_HOST),0,255),'time'=>gmdate('Y-m-d H:i:s'));
        $line=json_encode($row,JSON_UNESCAPED_SLASHES);
        if(!is_string($line)||strlen($line)>1599)return;
        @file_put_contents(WB_PATH.'/temp/security-center-traffic.log',$line."\n",FILE_APPEND|LOCK_EX);
    }

    public static function isTechnicalRequest()
    {
        if(PHP_SAPI==='cli')return true;
        if(strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH']??''))==='xmlhttprequest')return true;
        $path=(string)parse_url((string)($_SERVER['REQUEST_URI']??''),PHP_URL_PATH);
        return (bool)preg_match('~/(?:modules/)?worker/(?:cron|webcron|async|runner|dispatch|execute)(?:\.php)?(?:/|$)~i',$path);
    }

    public static function pending($limit=100)
    {
        $path=WB_PATH.'/temp/security-center-traffic.log';
        if(!is_file($path)||($size=@filesize($path))===false||$size<1)return array();
        $handle=@fopen($path,'rb');if(!$handle)return array();
        @flock($handle,LOCK_SH);$read=min($size,262144);if($size>$read)fseek($handle,-$read,SEEK_END);$data=(string)stream_get_contents($handle);@flock($handle,LOCK_UN);fclose($handle);
        $lines=preg_split('/\r?\n/',trim($data));$rows=array();
        foreach(array_reverse($lines?:array()) as $line){$row=json_decode($line,true);if(!is_array($row))continue;$rows[]=array('id'=>0,'ip_hash'=>(string)($row['ip']??''),'path'=>(string)($row['path']??''),'method'=>(string)($row['method']??''),'user_agent'=>(string)($row['ua']??''),'referrer_host'=>(string)($row['ref']??''),'created_at'=>(string)($row['time']??''));if(count($rows)>=$limit)break;}
        return $rows;
    }

    public static function import($database,$limit=500)
    {
        $path=WB_PATH.'/temp/security-center-traffic.log';
        if(!is_file($path))return array('message'=>self::text('traffic_none'));
        $processing=$path.'.processing';
        if(!@rename($path,$processing))return array('message'=>self::text('traffic_busy'));
        $limit=max(1,min(5000,(int)$limit));$count=0;$remaining=array();$handle=@fopen($processing,'rb');
        if($handle){
            while(($line=fgets($handle))!==false){
                if($count>=$limit){$remaining[]=$line;continue;}
                $row=json_decode($line,true);if(!is_array($row))continue;
                $result=$database->query("INSERT INTO `{TP}mod_security_center_visits` (ip_hash,path,method,user_agent,referrer_host,created_at) VALUES ('".$database->escapeString((string)($row['ip']??''))."','".$database->escapeString((string)($row['path']??''))."','".$database->escapeString((string)($row['method']??''))."','".$database->escapeString((string)($row['ua']??''))."','".$database->escapeString((string)($row['ref']??''))."','".$database->escapeString((string)($row['time']??''))."')");
                if($result===false){$remaining[]=$line;continue;}$count++;
            }
            fclose($handle);
        }
        if($remaining)@file_put_contents($path,implode('',$remaining),FILE_APPEND|LOCK_EX);
        @unlink($processing);
        return array('message'=>self::text('traffic_done',array('{count}'=>$count)),'more'=>!empty($remaining));
    }
}
