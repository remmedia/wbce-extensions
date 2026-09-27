<?php
final class WbceSecurityCenterRuntimeConfig
{
    private static $settings;
    public static function path(){return WB_PATH.'/temp/security-center-runtime.json';}
    public static function load()
    {
        if(is_array(self::$settings))return self::$settings;self::$settings=array('firewall_enabled'=>'1','scanner_enabled'=>'1','virus_scanner_enabled'=>'1','virus_scan_archives'=>'1','integrity_enabled'=>'1','security_headers'=>'1','block_php_uploads'=>'1','sqli_protection'=>'1','xss_protection'=>'1','path_protection'=>'1','bad_bot_protection'=>'1','fake_bot_protection'=>'1','spam_protection'=>'1','proxy_protection'=>'0','bad_words_filter'=>'0','missing_user_agent_block'=>'0','request_size_limit'=>'1048576','auto_ban_enabled'=>'1','auto_ban_threshold'=>'5','auto_ban_minutes'=>'60','login_attempt_limit'=>'10','login_window_minutes'=>'15','traffic_logging'=>'0','traffic_sample_rate'=>'10','compiled_rules'=>array(),'php_runtime_settings'=>array());$data=@file_get_contents(self::path());if(is_string($data)&&strlen($data)<262144){$decoded=json_decode($data,true);if(is_array($decoded))self::$settings=array_merge(self::$settings,$decoded);}return self::$settings;
    }
    public static function write(array $settings)
    {
        require_once __DIR__.'/Language.php';
        $path=self::path();
        try{$suffix=bin2hex(random_bytes(4));}catch(Throwable $exception){throw new RuntimeException(WbceSecurityCenterLanguage::text('errors.secure_random','Die sichere Konfigurationsfreigabe konnte nicht erzeugt werden.'),0,$exception);}
        $temporary=$path.'.tmp-'.$suffix;
        $data=json_encode($settings,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
        if(!is_string($data)||!is_dir(dirname($path))||file_put_contents($temporary,$data,LOCK_EX)===false||!rename($temporary,$path)){
            @unlink($temporary);
            throw new RuntimeException(WbceSecurityCenterLanguage::text('errors.runtime_write','Die lokale Firewall-Konfiguration konnte nicht aktualisiert werden.'));
        }
        @chmod($path,0640);self::$settings=$settings;if(function_exists('opcache_invalidate'))@opcache_invalidate($path,true);
    }
}
