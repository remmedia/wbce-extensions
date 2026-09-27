<?php
require_once __DIR__.'/Language.php';
require_once __DIR__.'/Service.php';

final class WbceSecurityCenterWorker
{
    private static function text($key,array $replace=array()){return WbceSecurityCenterLanguage::text('worker_runtime.'.$key,$key,$replace);}

    public static function scan($configuration=array())
    {
		require_once __DIR__.'/Scanner.php';
        global $database;$service=new WbceSecurityCenterService($database);$settings=$service->settings();
        if(($settings['scanner_enabled']??'1')!=='1')return array('message'=>self::text('code_disabled'));
        $active=$database->query("SELECT id FROM `{TP}mod_security_center_scan_queue` WHERE status IN ('queued','running') LIMIT 1");
        if(!$active||!$active->fetchRow(MYSQLI_ASSOC)){$recent=$database->query("SELECT id FROM `{TP}mod_security_center_scan_queue` WHERE created_at>='".gmdate('Y-m-d H:i:s',time()-7*86400)."' LIMIT 1");if(!$recent||!$recent->fetchRow(MYSQLI_ASSOC))$service->queueScan();}
        return (new WbceSecurityCenterScanner($database))->run((int)($settings['scanner_files_per_run']??($configuration['files_per_run']??120)));
    }

    public static function scheduledScan($configuration=array(),$task=array())
    {
		require_once __DIR__.'/Scanner.php';
        global $database;$service=new WbceSecurityCenterService($database);$settings=$service->settings();
        if(($settings['scanner_enabled']??'1')!=='1')throw new RuntimeException(self::text('code_disabled'));
        $running=$database->query("SELECT id FROM `{TP}mod_security_center_scan_queue` WHERE status IN ('queued','running') LIMIT 1");if(!$running||!$running->fetchRow(MYSQLI_ASSOC))$service->queueScan();
        $result=(new WbceSecurityCenterScanner($database))->run((int)($settings['scanner_files_per_run']??120));self::finishOnce($database,$configuration,$task);return $result;
    }

    public static function virusScan($configuration=array())
    {
		require_once __DIR__.'/VirusScanner.php';
        global $database;$service=new WbceSecurityCenterService($database);$settings=$service->settings();
        if(($settings['virus_scanner_enabled']??'1')!=='1')return array('message'=>self::text('virus_disabled'));
        return (new WbceSecurityCenterVirusScanner($database))->run((int)($settings['virus_files_per_run']??($configuration['files_per_run']??80)),(int)($settings['virus_max_file_mb']??16)*1048576,($settings['virus_scan_archives']??'1')==='1');
    }

    public static function scheduledVirusScan($configuration=array(),$task=array())
    {
		require_once __DIR__.'/VirusScanner.php';
        global $database;$service=new WbceSecurityCenterService($database);$settings=$service->settings();
        if(($settings['virus_scanner_enabled']??'1')!=='1')throw new RuntimeException(self::text('virus_disabled'));
        $running=$database->query("SELECT id FROM `{TP}mod_security_center_virus_queue` WHERE status IN ('queued','running') LIMIT 1");if(!$running||!$running->fetchRow(MYSQLI_ASSOC))$service->queueVirusScan();
        $result=(new WbceSecurityCenterVirusScanner($database))->run((int)($settings['virus_files_per_run']??80),(int)($settings['virus_max_file_mb']??16)*1048576,($settings['virus_scan_archives']??'1')==='1');self::finishOnce($database,$configuration,$task);return $result;
    }

    private static function finishOnce($database,$configuration,$task){if(!empty($configuration['once'])&&!empty($task['id']))$database->query('UPDATE `{TP}mod_worker_tasks` SET active=0,next_run=NULL WHERE id='.(int)$task['id']);}
    public static function integrity($configuration=array()){require_once __DIR__.'/IntegrityMonitor.php';global $database;$settings=(new WbceSecurityCenterService($database))->settings();return WbceSecurityCenterIntegrityMonitor::run($database,(int)($settings['integrity_files_per_run']??($configuration['files_per_run']??250)));}
    public static function traffic($configuration=array()){require_once __DIR__.'/TrafficBuffer.php';global $database;return WbceSecurityCenterTrafficBuffer::import($database,(int)($configuration['items_per_run']??500));}
    public static function botVerification($configuration=array()){require_once __DIR__.'/BotVerifier.php';global $database;return WbceSecurityCenterBotVerifier::run($database,(int)($configuration['items_per_run']??20));}

    public static function diagnostics($configuration=array())
    {
        global $database;$stale=gmdate('Y-m-d H:i:s',time()-1800);$database->query("UPDATE `{TP}mod_security_center_diagnostics` SET status='queued',started_at=NULL WHERE status='running' AND started_at<'$stale'");
        $result=$database->query("SELECT * FROM `{TP}mod_security_center_diagnostics` WHERE status='queued' ORDER BY id LIMIT 1");$job=$result?$result->fetchRow(MYSQLI_ASSOC):null;if(!$job)return array('message'=>self::text('diagnostic_none'));
        $id=(int)$job['id'];$now=gmdate('Y-m-d H:i:s');$database->query("UPDATE `{TP}mod_security_center_diagnostics` SET status='running',started_at='$now' WHERE id=$id AND status='queued'");
        try{$details=self::runDiagnostic($job);$encoded=json_encode($details,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);if(!is_string($encoded))throw new RuntimeException(self::text('diagnostic_encode_failed'));$database->query("UPDATE `{TP}mod_security_center_diagnostics` SET status='done',result='".$database->escapeString($encoded)."',finished_at='".gmdate('Y-m-d H:i:s')."' WHERE id=$id");return array('message'=>self::text('diagnostic_done',array('{id}'=>$id)));}
        catch(Throwable $exception){$database->query("UPDATE `{TP}mod_security_center_diagnostics` SET status='failed',result='".$database->escapeString($exception->getMessage())."',finished_at='".gmdate('Y-m-d H:i:s')."' WHERE id=$id");throw $exception;}
    }

    private static function runDiagnostic($job)
    {
        $type=(string)$job['job_type'];$target=(string)$job['target'];
        if($type==='ip_lookup')return array('ip'=>$target,'hostname'=>gethostbyaddr($target)?:self::text('unavailable'));
        if($type==='fake_bot'){$host=strtolower((string)gethostbyaddr($target));$recognized=(bool)preg_match('/\.(?:googlebot\.com|google\.com|search\.msn\.com|bing\.com)$/',$host);$forward=$recognized?gethostbynamel($host):array();return array('ip'=>$target,'hostname'=>$host?:self::text('unavailable'),'verified'=>$recognized&&is_array($forward)&&in_array($target,$forward,true));}
        if($type==='dnsbl'){if(filter_var($target,FILTER_VALIDATE_IP,FILTER_FLAG_IPV4)===false)throw new RuntimeException(self::text('dnsbl_ipv4'));$reverse=implode('.',array_reverse(explode('.',$target)));$listed=array();foreach(array('zen.spamhaus.org','bl.spamcop.net') as $zone){$query=$reverse.'.'.$zone;$answer=gethostbyname($query);if($answer!==$query)$listed[]=$zone;}return array('ip'=>$target,'listed'=>$listed);}
        if($type==='port_scan'){$config=json_decode((string)$job['configuration'],true)?:array();$ports=array();foreach((array)($config['ports']??array()) as $port){$start=microtime(true);$socket=@fsockopen($target,(int)$port,$errno,$error,.35);$ports[(int)$port]=array('open'=>(bool)$socket,'milliseconds'=>(int)round((microtime(true)-$start)*1000));if($socket)fclose($socket);}return array('host'=>(string)($config['display_host']??$target),'address'=>$target,'ports'=>$ports);}
        throw new RuntimeException(self::text('diagnostic_unknown'));
    }

    public static function notifications($configuration=array())
    {
        global $database;$service=new WbceSecurityCenterService($database);$settings=$service->settings();if(($settings['email_notifications']??'0')!=='1'||empty($settings['notification_email']))return array('message'=>self::text('notifications_disabled'));
        $result=$database->query("SELECT n.id,e.event_type,e.severity,e.request_path,e.created_at FROM `{TP}mod_security_center_notifications` n JOIN `{TP}mod_security_center_events` e ON e.id=n.event_id WHERE n.status='queued' ORDER BY n.id LIMIT 20");$sent=0;
        while($result&&($row=$result->fetchRow(MYSQLI_ASSOC))){$subject=self::text('mail_subject',array('{severity}'=>$row['severity']));$body=self::text('mail_body',array('{event}'=>$row['event_type'],'{severity}'=>$row['severity'],'{path}'=>$row['request_path'],'{time}'=>self::displayTime((string)$row['created_at'])));$ok=@mail((string)$settings['notification_email'],$subject,$body);$database->query("UPDATE `{TP}mod_security_center_notifications` SET status='".($ok?'sent':'failed')."',attempts=attempts+1,processed_at='".gmdate('Y-m-d H:i:s')."' WHERE id=".(int)$row['id']);if($ok)$sent++;}
        return array('message'=>self::text('notifications_sent',array('{count}'=>$sent)));
    }

    private static function displayTime($utc){try{if(function_exists('wbce_format_utc_datetime'))return wbce_format_utc_datetime($utc,'d.m.Y H:i:s');$date=DateTimeImmutable::createFromFormat('!Y-m-d H:i:s',$utc,new DateTimeZone('UTC'));$zone=function_exists('wbce_timezone')?wbce_timezone():new DateTimeZone(date_default_timezone_get());return $date?$date->setTimezone($zone)->format('d.m.Y H:i:s'):$utc;}catch(Throwable $exception){return $utc;}}

    public static function cleanup($configuration=array())
    {
        global $database;$settings=(new WbceSecurityCenterService($database))->settings();$before=gmdate('Y-m-d H:i:s',time()-max(1,min(365,(int)$settings['log_retention_days']))*86400);$trafficBefore=gmdate('Y-m-d H:i:s',time()-max(1,min(90,(int)$settings['traffic_retention_days']))*86400);$now=gmdate('Y-m-d H:i:s');
        $database->query("DELETE FROM `{TP}mod_security_center_events` WHERE created_at<'$before'");$database->query("DELETE FROM `{TP}mod_security_center_scan_queue` WHERE status IN ('done','failed') AND finished_at<'$before'");$database->query("DELETE FROM `{TP}mod_security_center_virus_queue` WHERE status IN ('done','failed') AND finished_at<'$before'");$database->query("DELETE FROM `{TP}mod_security_center_visits` WHERE created_at<'$trafficBefore'");$database->query("DELETE FROM `{TP}mod_security_center_diagnostics` WHERE status IN ('done','failed') AND finished_at<'$before'");$database->query("DELETE FROM `{TP}mod_security_center_bot_cache` WHERE expires_at<'$now'");return array('message'=>self::text('cleanup_done'));
    }
}
