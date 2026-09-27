<?php
require_once __DIR__.'/Language.php';
final class WbceSecurityCenterBotVerifier
{
    public static function queue($ip,$userAgent)
    {
        if(filter_var($ip,FILTER_VALIDATE_IP)===false)return;
        $path=WB_PATH.'/temp/security-center-bots.log';
        $row=json_encode(array('ip'=>$ip,'ua'=>substr((string)$userAgent,0,300),'queued_at'=>gmdate('Y-m-d H:i:s')),JSON_UNESCAPED_SLASHES)."\n";
        @file_put_contents($path,$row,FILE_APPEND|LOCK_EX);@chmod($path,0600);
    }
    public static function run($database,$limit=20)
    {
        $path=WB_PATH.'/temp/security-center-bots.log';if(!is_file($path))return array('message'=>self::text('bot_none'));require_once __DIR__.'/Service.php';$settings=(new WbceSecurityCenterService($database))->settings();$cacheSeconds=max(1,min(720,(int)($settings['fake_bot_cache_hours']??24)))*3600;
        $processing=$path.'.processing';if(!@rename($path,$processing))return array('message'=>self::text('bot_busy'));
        $lines=@file($processing,FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES)?:array();$process=array_slice($lines,0,max(1,min(100,(int)$limit)));$remaining=array_slice($lines,count($process));
        if($remaining)@file_put_contents($path,implode("\n",$remaining)."\n",FILE_APPEND|LOCK_EX);@unlink($processing);$checked=0;
        foreach($process as $line){$row=json_decode($line,true);if(!is_array($row)||filter_var($row['ip']??'',FILTER_VALIDATE_IP)===false)continue;$ip=(string)$row['ip'];$host=strtolower((string)gethostbyaddr($ip));$recognized=(bool)preg_match('/\.(?:googlebot\.com|google\.com|search\.msn\.com|bing\.com)$/',$host);$forward=$recognized?gethostbynamel($host):array();$verified=$recognized&&is_array($forward)&&in_array($ip,$forward,true);$hash=hash_hmac('sha256',$ip,defined('DB_PASSWORD')?(string)DB_PASSWORD:__DIR__);$database->query("REPLACE INTO `{TP}mod_security_center_bot_cache` (ip_hash,hostname,verified,checked_at,expires_at) VALUES ('".$database->escapeString($hash)."','".$database->escapeString(substr($host,0,255))."',".($verified?1:0).",'".gmdate('Y-m-d H:i:s')."','".gmdate('Y-m-d H:i:s',time()+$cacheSeconds)."')");$checked++;}
        WbceSecurityCenterService::refreshRuntime($database);return array('message'=>self::text('bot_done',array('{count}'=>$checked)));
    }
    private static function text($key,array $replace=array()){return WbceSecurityCenterLanguage::text('worker_runtime.'.$key,$key,$replace);}
}
