<?php
final class WbceSecurityCenterPhpRuntime
{
    public static function allowed()
    {
        return array('display_errors','display_startup_errors','log_errors','expose_php','allow_url_include','session.cookie_httponly','session.use_strict_mode','session.use_only_cookies','session.use_trans_sid','phar.require_hash','phar.readonly');
    }

    public static function apply(array $settings)
    {
        $values=(array)($settings['php_runtime_settings']??array());
        foreach($values as $name=>$value){
            if(!in_array($name,self::allowed(),true)||($value!=='0'&&$value!=='1'))continue;
            // Session directives cannot be changed after WBCE started the
            // session. They are applied during preinit only; other directives
            // are deliberately applied again during initialize.
            if(function_exists('session_status')&&session_status()===PHP_SESSION_ACTIVE&&str_starts_with($name,'session.'))continue;
            @ini_set($name,$value);
        }
    }
}
