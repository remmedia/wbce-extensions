<?php
defined('WB_PATH') or die('No direct access');

final class WbceAccessibilityToolsLegacyOutputBuffer
{
    private static $started=false;

    public static function start()
    {
        if(self::$started||PHP_SAPI==='cli')return;
        $method=strtoupper(isset($_SERVER['REQUEST_METHOD'])&&is_string($_SERVER['REQUEST_METHOD'])?$_SERVER['REQUEST_METHOD']:'GET');
        if($method!=='GET'&&$method!=='HEAD')return;
        $path=(string)parse_url(isset($_SERVER['REQUEST_URI'])?$_SERVER['REQUEST_URI']:'',PHP_URL_PATH);
        $adminPath=defined('ADMIN_URL')?(string)parse_url(ADMIN_URL,PHP_URL_PATH):'/admin';
        $adminPath=rtrim($adminPath,'/');
        if($adminPath!==''&&($path===$adminPath||strpos($path,$adminPath.'/')===0))return;
        // Never buffer API, module, asset or download responses. Apart from avoiding
        // accidental output changes, this keeps the legacy fallback cheap for large files.
        $relative=ltrim($path,'/');
        foreach(array('modules/','api/','media/') as $prefix)if(strpos($relative,$prefix)===0)return;
        if(preg_match('/\.(?:css|js|mjs|json|xml|txt|csv|pdf|zip|gz|tar|jpe?g|png|gif|webp|avif|svg|ico|woff2?|ttf|eot|mp[34]|webm)$/i',$path))return;
        $accept=isset($_SERVER['HTTP_ACCEPT'])&&is_string($_SERVER['HTTP_ACCEPT'])?strtolower($_SERVER['HTTP_ACCEPT']):'';
        if($accept!==''&&strpos($accept,'text/html')===false&&strpos($accept,'application/xhtml+xml')===false&&strpos($accept,'*/*')===false)return;
        self::$started=true;
        ob_start(static function($html){return WbceAccessibilityToolsInjector::inject($html);});
    }
}
