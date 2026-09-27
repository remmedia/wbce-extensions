<?php
require_once __DIR__.'/Language.php';
require_once __DIR__.'/Settings.php';
final class WbceWorkerAsyncLauncher
{
    public static function launch(int $taskId, string $token): void
    {
        global $database;
        $base=parse_url(WB_URL);$host=(string)($base['host']??'');$scheme=strtolower((string)($base['scheme']??'https'));
        if($host===''||!in_array($scheme,array('http','https'),true))throw new RuntimeException(worker_t('internal_address_invalid'));
        $port=isset($base['port'])?(int)$base['port']:($scheme==='https'?443:80);
        $secret=WbceWorkerSettings::get($database,'url_token','');if($secret==='')throw new RuntimeException(worker_t('internal_secret_missing'));
        $signature=hash_hmac('sha256',$taskId.'|'.$token,$secret);
        $root=rtrim((string)($base['path']??''),'/');$path=$root.'/modules/worker/execute.php?task='.$taskId.'&token='.rawurlencode($token).'&signature='.$signature;
        $transport=$scheme==='https'?'ssl://'.$host:$host;
        // Loopback launches must fail fast. A blocked internal TLS connection
        // must never hold the website-cron for seconds per due task.
        $socket=@fsockopen($transport,$port,$errorNumber,$errorMessage,0.15);
        if(!$socket)throw new RuntimeException(worker_t('async_connect_failed',array('{error}'=>$errorMessage!==''?$errorMessage:worker_t('connection_failed'))));
        stream_set_blocking($socket,true);stream_set_timeout($socket,0,150000);
        $hostHeader=$host.(isset($base['port'])?':'.$port:'');
        $request="GET ".$path." HTTP/1.1\r\nHost: ".$hostHeader."\r\nConnection: close\r\nUser-Agent: WBCE-Worker/1.10.42\r\n\r\n";
        $length=strlen($request);$offset=0;
        while($offset<$length){$written=@fwrite($socket,substr($request,$offset));if($written===false||$written===0)break;$offset+=$written;}
        fclose($socket);
        if($offset<$length)throw new RuntimeException(worker_t('async_request_incomplete'));
    }
}
