<?php
final class WbceTwoFactorSettings
{
    public static function install($database)
    {
        $database->query("CREATE TABLE IF NOT EXISTS `{TP}mod_two_factor_settings` (`name` varchar(80) NOT NULL,`value` text NOT NULL,PRIMARY KEY (`name`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $database->query("CREATE TABLE IF NOT EXISTS `{TP}mod_two_factor_users` (`user_id` int NOT NULL,`selected_provider` varchar(64) NOT NULL DEFAULT '',PRIMARY KEY (`user_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
    public static function get($database,$name,$default=''){$r=$database->query("SELECT `value` FROM `{TP}mod_two_factor_settings` WHERE `name`='".$database->escapeString((string)$name)."' LIMIT 1");$row=$r?$r->fetchRow(MYSQLI_ASSOC):null;return $row?(string)$row['value']:(string)$default;}
    public static function set($database,$name,$value){return(bool)$database->query("INSERT INTO `{TP}mod_two_factor_settings` (`name`,`value`) VALUES ('".$database->escapeString((string)$name)."','".$database->escapeString((string)$value)."') ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)");}
    /** Last provider focused in administration; retained while global 2FA is off. */
    public static function selected($database){return self::get($database,'selected_provider','');}
    public static function providerEnabled($database,$id)
    {
        $saved=self::get($database,'provider_enabled.'.strtolower((string)$id),'__missing__');
        return $saved==='__missing__'?self::selected($database)===strtolower((string)$id):$saved==='1';
    }
    public static function setProviderEnabled($database,$id,$enabled)
    {
        $ok=self::set($database,'provider_enabled.'.strtolower((string)$id),$enabled?'1':'0');
        if(!$enabled&&self::enabledProviderIds($database)===array())self::set($database,'enabled','0');
        return $ok;
    }
    public static function enabledProviderIds($database)
    {
        if(!class_exists('WbceTwoFactorRegistry',false))return array();$out=array();
        foreach(WbceTwoFactorRegistry::providers() as $id=>$provider)if(self::providerEnabled($database,$id))$out[]=$id;
        return $out;
    }
    public static function enabled($database){return self::get($database,'enabled','0')==='1'&&self::enabledProviderIds($database)!==array();}
    public static function userProvider($database,$userId)
    {
        $r=$database->query('SELECT `selected_provider` FROM `{TP}mod_two_factor_users` WHERE `user_id`='.(int)$userId.' LIMIT 1');$row=$r?$r->fetchRow(MYSQLI_ASSOC):null;$id=$row?(string)$row['selected_provider']:'';
        $registered=class_exists('WbceTwoFactorRegistry',false)?WbceTwoFactorRegistry::providers():array();
        if($id!==''&&isset($registered[$id])&&self::providerEnabled($database,$id))return $id;
        // An existing empty row is an explicit personal opt-out. Do not revive
        // the former global provider through the one-time legacy migration.
        if($row)return '';
        $legacy=self::selected($database);$providers=$registered;
        if($legacy!==''&&self::providerEnabled($database,$legacy)&&isset($providers[$legacy])&&!empty($providers[$legacy]['configured'])&&is_callable($providers[$legacy]['configured'])&&call_user_func($providers[$legacy]['configured'],(int)$userId)){self::setUserProvider($database,$userId,$legacy);return $legacy;}
        return '';
    }
    public static function setUserProvider($database,$userId,$id)
    {
        $id=strtolower(trim((string)$id));
        if($id!==''&&!self::providerEnabled($database,$id))return false;
        return(bool)$database->query("INSERT INTO `{TP}mod_two_factor_users` (`user_id`,`selected_provider`) VALUES (".(int)$userId.",'".$database->escapeString($id)."') ON DUPLICATE KEY UPDATE `selected_provider`=VALUES(`selected_provider`)");
    }
    public static function removeProviderData($database,$id)
    {
        $id=strtolower(trim((string)$id));$escaped=$database->escapeString($id);
        $ok=(bool)$database->query("UPDATE `{TP}mod_two_factor_users` SET `selected_provider`='' WHERE `selected_provider`='".$escaped."'");
        $ok=(bool)$database->query("DELETE FROM `{TP}mod_two_factor_settings` WHERE `name`='provider_enabled.".$escaped."'")&&$ok;
        if(self::selected($database)===$id)$ok=self::set($database,'selected_provider','')&&$ok;
        return $ok;
    }
}
