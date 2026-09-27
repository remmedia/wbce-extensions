<?php
defined('WB_PATH') or die('No direct access');
require_once __DIR__.'/Client.php';
require_once __DIR__.'/Language.php';
final class WbceLogCenterHeartbeat
{
    public static function run(array $configuration=array(),array $task=array()): array
    {
        $domain=WbceLogCenterClient::normalizeDomainInput(Settings::GetDb('log_center_url',''));$token=trim((string)Settings::GetDb('log_center_token',''));
        if(!filter_var(Settings::GetDb('log_center_connection_enabled',true),FILTER_VALIDATE_BOOLEAN)||$domain===''||$token==='')return array('message'=>log_center_text('HEARTBEAT_DISABLED'));
        list($ok,$detail,$endpoint)=WbceLogCenterClient::probe($domain,$token);Settings::Set('log_center_connection_ok',$ok);Settings::Set('log_center_connection_checked_at',time());Settings::Set('log_center_heartbeat_at',time());if($ok&&$endpoint!=='')Settings::Set('log_center_endpoint',$endpoint);
        return array('message'=>$ok?log_center_text('HEARTBEAT_OK'):log_center_text('HEARTBEAT_FAILED').' '.$detail);
    }
}
