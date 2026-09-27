<?php
defined('WB_PATH') or die('No direct access');
require_once __DIR__.'/Language.php';
final class WbceLogCenterClient
{
    private static $shutdownRegistered=false;
    private static $dispatching=false;
    public static function enabled($type){if(!class_exists('Settings'))return false;$type=self::type($type);return $type!==''&&self::enabledFlag($type)&&self::connection()!==null;}
    public static function sendEvent($type,$severity,$source,$message,$trace=''){return self::send($type,array(array('time'=>gmdate('c'),'severity'=>(string)$severity,'source'=>(string)$source,'message'=>(string)$message,'trace'=>(string)$trace,'line'=>trim((string)$message))));}
    public static function send($type,array $entries)
    {
        $type=self::type($type);
        if($type===''||!self::enabledFlag($type)||self::connection()===null||!$entries)return false;
        if(!self::enqueue(array('type'=>$type,'entries'=>array_slice($entries,0,100))))return false;
        // Producers never wait for the remote endpoint.  The managed
        // log_center.delivery worker owns all HTTP delivery and retries.
        return true;
    }
    /**
     * Best-effort, short-lived delivery for a completed web response.
     * The caller must only invoke this after fastcgi_finish_request().  A
     * failed attempt intentionally leaves the already persisted queue intact.
     */
    public static function deliverAfterResponse($type,array $entries,$timeout=80)
    {
        $type=self::type($type);$timeout=max(25,min(150,(int)$timeout));
        if($type===''||!self::enabledFlag($type)||!$entries)return false;
        $connection=self::connection();if($connection===null)return false;
        $payload=json_encode(array('schema'=>1,'log_type'=>$type,'domain'=>self::domain(),'entries'=>array_slice($entries,0,100)),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        if(!is_string($payload))return false;
        list($sent)=self::request($connection[0],$connection[1],$payload,$timeout);
        if(!$sent){$fallback=self::apiFallback($connection[0]);if($fallback!=='')list($sent)=self::request($fallback,$connection[1],$payload,$timeout);}
        return $sent;
    }
    public static function dispatchQueued()
    {
        // Kept as a compatibility no-op for Error Logger.  Delivery is
        // deliberately deferred to the managed queue worker.
        return;
    }
    public static function flushQueue()
    {
        if(self::$dispatching)return false;
        $connection=self::connection();if($connection===null)return false;
        self::$dispatching=true;$claim=self::claimQueue();
        if($claim===''){self::$dispatching=false;return true;}
        $lines=@file($claim,FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES);$retry=array();$ok=true;
        foreach((array)$lines as $line){
            $item=json_decode($line,true);if(!is_array($item)||empty($item['type'])||empty($item['entries']))continue;
            $payload=json_encode(array('schema'=>1,'log_type'=>(string)$item['type'],'domain'=>self::domain(),'entries'=>array_slice((array)$item['entries'],0,100)),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
            if(!is_string($payload))continue;
            list($sent)=self::request($connection[0],$connection[1],$payload,300);
            if(!$sent){
                $fallback=self::apiFallback($connection[0]);
                if($fallback!=='')list($sent)=self::request($fallback,$connection[1],$payload,300);
            }
            if(!$sent){$retry[]=$line;$ok=false;}
        }
        @unlink($claim);if($retry)self::appendLines($retry);self::$dispatching=false;return $ok;
    }
    public static function probe($url,$token){$token=trim((string)$token);$host=self::normalizeDomainInput($url);if($token===''||self::domain()===''||$host==='')return array(false,'invalid_configuration','');$last='invalid_configuration';foreach(self::candidateUrls($host) as $candidate){$payload=json_encode(array('schema'=>1,'probe'=>true,'log_type'=>'error','domain'=>self::domain(),'entries'=>array()),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);list($ok,$detail)=self::request($candidate,$token,(string)$payload,1500);if($ok)return array(true,$detail,$candidate);$last=$detail;}return array(false,$last,'');}
    public static function normalizeDomainInput($value){$value=trim((string)$value);if($value==='')return '';$candidate=preg_match('#^https?://#i',$value)?$value:'https://'.$value;$parts=parse_url($candidate);if(!is_array($parts)||empty($parts['host'])||!empty($parts['user'])||!empty($parts['pass']))return '';$host=strtolower(rtrim((string)$parts['host'],'.'));if(!preg_match('/^(?=.{1,253}$)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)*[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/',$host))return '';return $host;}
    private static function enqueue(array $item){$line=json_encode($item,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);return is_string($line)&&self::appendLines(array($line));}
    private static function appendLines(array $lines){$path=self::queuePath();$dir=dirname($path);if(!is_dir($dir)&&!@mkdir($dir,0750,true)&&!is_dir($dir))return false;$written=@file_put_contents($path,implode("\n",$lines)."\n",FILE_APPEND|LOCK_EX);if($written!==false)@chmod($path,0600);return $written!==false;}
    private static function claimQueue(){$path=self::queuePath();if(!is_file($path)||@filesize($path)<1)return '';$claim=$path.'.sending.'.getmypid().'.'.bin2hex(random_bytes(4));return @rename($path,$claim)?$claim:'';}
    private static function queuePath(){return WB_PATH.'/var/logs/log_center_queue.ndjson';}
    private static function candidateUrls($domain){$origin='https://'.self::normalizeDomainInput($domain);return $origin==='https://'?array():array($origin.'/modules/api/log/',$origin.'/modules/log_server/api/');}
    private static function apiFallback($url){$parts=parse_url((string)$url);if(!is_array($parts)||empty($parts['host']))return '';$path=rtrim((string)($parts['path']??''),'/').'/';if($path!=='/modules/log_server/api/')return '';$port=isset($parts['port'])?':'.(int)$parts['port']:'';return 'https://'.$parts['host'].$port.'/modules/api/log/';}
    private static function request($url,$token,$payload,$timeout){$status=0;$body=false;if(function_exists('curl_init')){$h=curl_init($url);if($h===false)return array(false,'connection_failed');curl_setopt_array($h,array(CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>$payload,CURLOPT_HTTPHEADER=>array('Authorization: Bearer '.$token,'X-Log-Token: '.$token,'Content-Type: application/json','Accept: application/json'),CURLOPT_USERAGENT=>'WBCE Log Center/1.0',CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT_MS=>min(150,$timeout),CURLOPT_TIMEOUT_MS=>$timeout,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2,CURLOPT_FOLLOWLOCATION=>false));$body=@curl_exec($h);$status=(int)curl_getinfo($h,CURLINFO_RESPONSE_CODE);}else{$ctx=stream_context_create(array('http'=>array('method'=>'POST','header'=>"Authorization: Bearer {$token}\r\nContent-Type: application/json\r\nAccept: application/json\r\nUser-Agent: WBCE Log Center/1.0\r\nConnection: close\r\n",'content'=>$payload,'timeout'=>$timeout/1000,'ignore_errors'=>true),'ssl'=>array('verify_peer'=>true,'verify_peer_name'=>true)));$body=@file_get_contents($url,false,$ctx);$headers=function_exists('http_get_last_response_headers')?(array)http_get_last_response_headers():array();$status=isset($headers[0])&&preg_match('/\s(\d{3})\s/',$headers[0],$m)?(int)$m[1]:0;}$data=is_string($body)?json_decode($body,true):null;$ok=$status>=200&&$status<300&&is_array($data)&&!empty($data['ok']);return array($ok,is_array($data)&&isset($data['message'])?(string)$data['message']:($status?'HTTP '.$status:'connection_failed'));}
    private static function connection(){if(!filter_var(self::setting('log_center_connection_enabled',true),FILTER_VALIDATE_BOOLEAN))return null;$domain=self::normalizeDomainInput(self::setting('log_center_url',''));$endpoint=trim((string)self::setting('log_center_endpoint',''));$token=trim((string)self::setting('log_center_token',''));if(!self::validEndpoint($endpoint)&&$domain!=='')$endpoint='https://'.$domain.'/modules/api/log/';return self::validEndpoint($endpoint)&&$token!==''&&self::domain()!==''?array($endpoint,$token):null;}
    private static function enabledFlag($type){return filter_var(self::setting('log_center_'.$type.'_enabled',true),FILTER_VALIDATE_BOOLEAN);}
    private static function setting($name,$default){if(!class_exists('Settings'))return $default;try{if(method_exists('Settings','GetDb')||is_callable(array('Settings','GetDb')))return Settings::GetDb($name,$default);if(method_exists('Settings','getFromDb'))return Settings::getFromDb($name,$default);return Settings::Get($name,$default);}catch(Throwable $e){return $default;}}
    private static function type($type){$type=strtolower(trim((string)$type));if(!preg_match('/^[a-z][a-z0-9_-]{0,39}$/',$type))return '';$registry=__DIR__.'/Registry.php';if(is_file($registry))require_once $registry;return class_exists('WbceLogCenterRegistry')&&WbceLogCenterRegistry::has($type)?$type:'';}
    private static function validUrl($url){$p=parse_url((string)$url);return is_array($p)&&strtolower((string)($p['scheme']??''))==='https'&&!empty($p['host'])&&empty($p['user'])&&empty($p['pass'])&&empty($p['query'])&&empty($p['fragment']);}
    private static function validEndpoint($url){if(!self::validUrl($url))return false;$path=rtrim((string)(parse_url($url,PHP_URL_PATH)??''),'/').'/';return $path==='/modules/api/log/'||$path==='/modules/log_server/api/';}
    private static function domain(){$host=strtolower((string)preg_replace('/:\d+$/','',trim((string)($_SERVER['HTTP_HOST']??parse_url(defined('WB_URL')?WB_URL:'',PHP_URL_HOST)))));return preg_match('/^[a-z0-9.-]{1,253}$/',$host)?$host:'';}
}
