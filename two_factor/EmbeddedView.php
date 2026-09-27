<?php
final class WbceTwoFactorEmbeddedView
{
    public static function requested(){return isset($_GET['embedded'])&&$_GET['embedded']==='1';}
    public static function start()
    {
        if(!self::requested())return false;
        echo '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width">';
        if(defined('THEME_PATH')&&defined('THEME_URL'))foreach(array('css/style.min.css','css/admin-layout.css')as$file)if(is_file(THEME_PATH.'/'.$file))echo '<link rel="stylesheet" href="'.htmlspecialchars(THEME_URL.'/'.$file,ENT_QUOTES,'UTF-8').'">';
        echo '</head><body class="wbce-embedded-view"><main class="adminModuleWrapper wbce-admin-tool-shell wbce-admin-card">';
        return true;
    }
    public static function end(){if(self::requested())echo '</main></body></html>';}
    public static function suffix(){return self::requested()?'&embedded=1':'';}
}
