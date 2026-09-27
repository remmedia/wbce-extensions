<?php
defined('WB_PATH') or die('No direct access');
final class WbceAccessibilityToolsConfig
{
    private static $cache=null;
    public static function defaults(){return array('enabled'=>0,'position_desktop'=>'top_right','position_mobile'=>'bottom_left','invert'=>1,'grayscale'=>1,'saturation'=>1,'links'=>1,'font_size'=>1,'line_height'=>1,'letter_spacing'=>1,'text_align'=>1,'contrast'=>1,'hide_images'=>1,'hide_video'=>1,'cursor'=>1,'position_controls'=>1);}
    public static function positions(){return array('top_left','top','top_right','right','bottom_right','bottom','bottom_left','left');}
    private static function querySucceeded($result){return is_object($result)&&(!method_exists($result,'error')||!(string)$result->error());}
    public static function ensureSchema($db=null){if($db===null){global $database;$db=$database;}if(!is_object($db)||!method_exists($db,'query'))return false;return self::querySucceeded($db->query("CREATE TABLE IF NOT EXISTS `{TP}mod_accessibility_tools_settings` (`name` varchar(64) NOT NULL,`value` text NOT NULL,PRIMARY KEY (`name`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"));}
    private static function row($result){if(!is_object($result))return false;if(method_exists($result,'fetchRow'))return $result->fetchRow(defined('MYSQLI_ASSOC')?MYSQLI_ASSOC:1);if(method_exists($result,'fetch_assoc'))return $result->fetch_assoc();return false;}
    private static function escape($db,$value){if(method_exists($db,'escapeString'))return $db->escapeString((string)$value);if(method_exists($db,'escape_string'))return $db->escape_string((string)$value);return addslashes((string)$value);}
    public static function get($db=null){
        if(self::$cache!==null)return self::$cache;if($db===null){global $database;$db=$database;}$values=self::defaults();$legacyPosition='';$hasDesktopPosition=false;
        if(!is_object($db)||!method_exists($db,'query')||!self::ensureSchema($db))return self::$cache=$values;$result=$db->query('SELECT `name`,`value` FROM `{TP}mod_accessibility_tools_settings`');
        if(self::querySucceeded($result))while($row=self::row($result)){if($row['name']==='position')$legacyPosition=(string)$row['value'];if($row['name']==='position_desktop')$hasDesktopPosition=true;if(array_key_exists($row['name'],$values))$values[$row['name']]=$row['value'];}
        if(!$hasDesktopPosition&&$legacyPosition!==''&&in_array($legacyPosition,self::positions(),true))$values['position_desktop']=$legacyPosition;
        foreach($values as $key=>$value)if($key!=='position_desktop'&&$key!=='position_mobile')$values[$key]=(int)(bool)$value;
        if(!in_array($values['position_desktop'],self::positions(),true))$values['position_desktop']='top_right';if(!in_array($values['position_mobile'],self::positions(),true))$values['position_mobile']='bottom_left';return self::$cache=$values;
    }
    public static function save(array $input,$db=null){
        if($db===null){global $database;$db=$database;}require_once dirname(__DIR__).'/Language.php';$texts=accessibility_tools_texts();if(!is_object($db)||!method_exists($db,'query')||!self::ensureSchema($db))throw new RuntimeException($texts['database_unavailable']);$values=self::defaults();
        foreach($values as $key=>$default){$isPosition=$key==='position_desktop'||$key==='position_mobile';$raw=array_key_exists($key,$input)&&is_scalar($input[$key])?(string)$input[$key]:'';$value=$isPosition?($raw!==''?$raw:$default):($raw==='1'?'1':'0');if($isPosition&&!in_array($value,self::positions(),true))$value=$default;$name=self::escape($db,$key);$escaped=self::escape($db,$value);if(!self::querySucceeded($db->query("INSERT INTO `{TP}mod_accessibility_tools_settings` (`name`,`value`) VALUES ('$name','$escaped') ON DUPLICATE KEY UPDATE `value`='$escaped'")))throw new RuntimeException($texts['write_failed']);}
        self::$cache=null;return self::get($db);
    }
}
