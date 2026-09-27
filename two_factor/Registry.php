<?php
require_once __DIR__.'/Language.php';
final class WbceTwoFactorRegistry
{
    private static $providers=array();
    private static function row($result)
    {
        if (!is_object($result)) return null;
        if (is_callable(array($result, 'fetchRow'))) return $result->fetchRow(defined('MYSQLI_ASSOC') ? MYSQLI_ASSOC : 1);
        if (is_callable(array($result, 'fetch_assoc'))) return $result->fetch_assoc();
        return null;
    }
    public static function register($id,array $definition){$id=strtolower(trim((string)$id));if(!preg_match('/^[a-z][a-z0-9_.-]{1,63}$/',$id))throw new InvalidArgumentException(wbce_two_factor_t('invalid_provider_id'));self::$providers[$id]=$definition+array('id'=>$id,'name'=>$id,'description'=>'','icon'=>'fa-shield');}
    public static function providers(){return function_exists('wbce_apply_filters')?wbce_apply_filters('two_factor.providers',self::$providers):self::$providers;}
    public static function provider($id){$all=self::providers();return $all[$id]??null;}
    public static function usage($id){$provider=self::provider($id);if(!$provider||!isset($provider['usage'])||!is_callable($provider['usage']))return 0;try{return max(0,(int)call_user_func($provider['usage']));}catch(Throwable $ignored){return 0;}}
    public static function installedProviderModules($database)
    {
        $modules=array();
        if(!is_object($database)||!is_callable(array($database,'query')))return $modules;
        $result=$database->query("SELECT `directory` FROM `{TP}addons` WHERE `type`='module'");
        while($result&&($row=self::row($result))){
            $directory=(string)$row['directory'];
            if(strpos($directory,'two_factor_')===0&&is_file(WB_PATH.'/modules/'.$directory.'/info.php'))$modules[]=$directory;
        }
        return array_values(array_unique($modules));
    }
    public static function providerIdForModule($directory)
    {
        $directory=strtolower(trim((string)$directory));
        foreach(self::providers() as $id=>$provider){
            $module=isset($provider['module_directory'])?(string)$provider['module_directory']:'two_factor_'.$id;
            if($module===$directory)return (string)$id;
        }
        return strpos($directory,'two_factor_')===0?substr($directory,11):null;
    }
    public static function canUninstallModule($database,$directory)
    {
        if(self::providerIdForModule($directory)===null||count(self::installedProviderModules($database))>1)return true;
        $result=is_object($database)?$database->query("SELECT `value` FROM `{TP}mod_two_factor_settings` WHERE `name`='enabled' LIMIT 1"):null;
        $row=self::row($result);
        return !$row||(string)$row['value']!=='1';
    }
    public static function assertCanUninstall($id,$database=null)
    {
        if($database===null){global $database;}
        $providers=self::providers();
        $directory=isset($providers[$id]['module_directory'])?(string)$providers[$id]['module_directory']:'two_factor_'.$id;
        if(isset($providers[$id]) && !self::canUninstallModule($database,$directory)){
            throw new RuntimeException(wbce_two_factor_t('last_provider'));
        }
    }
}
