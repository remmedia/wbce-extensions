<?php
defined('WB_PATH') or die('No direct access');

final class WbceAccessibilityToolsOpfFallback
{
    const FILTER_NAME='WBCE Accessibility Tools';

    private static function load()
    {
        if(function_exists('opf_register_filter'))return true;
        $file=WB_PATH.'/modules/outputfilter_dashboard/functions.php';
        if(!is_file($file))return false;
        require_once $file;
        return function_exists('opf_register_filter');
    }

    public static function unregister()
    {
        if(!self::load()||!function_exists('opf_unregister_filter'))return false;
        try{return (bool)opf_unregister_filter(self::FILTER_NAME);}catch(Throwable $exception){return false;}
    }
}
