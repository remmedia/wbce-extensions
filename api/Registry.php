<?php
defined('WB_PATH') or die('Access denied');
require_once __DIR__ . '/Language.php';

final class WbceApiRegistry
{
    private static $schemaReady = false;

    private static function table() { return TABLE_PREFIX.'mod_api_routes'; }

    private static function row($result)
    {
        if (!$result) return null;
        if (is_object($result) && method_exists($result, 'error') && $result->error()) return null;
        try {
            if (is_object($result) && method_exists($result, 'fetchRow')) return $result->fetchRow(MYSQLI_ASSOC);
            if (is_object($result) && method_exists($result, 'fetch_assoc')) return $result->fetch_assoc();
            return function_exists('mysqli_fetch_assoc') && $result instanceof mysqli_result ? mysqli_fetch_assoc($result) : null;
        } catch (Throwable $ignored) {
            return null;
        }
    }

    private static function querySucceeded($result)
    {
        if ($result === false || $result === null) return false;
        return !(is_object($result) && method_exists($result, 'error') && $result->error());
    }

    public static function install($database)
    {
        if (self::$schemaReady) return;
        if (!is_object($database) || !method_exists($database, 'query')) throw new RuntimeException(wbce_api_t('database_unavailable'));
        $table=self::table();
        $result=$database->query("CREATE TABLE IF NOT EXISTS `$table` (`api_name` VARCHAR(64) NOT NULL,`module_directory` VARCHAR(190) NOT NULL,`handler` VARCHAR(255) NOT NULL,`created_at` INT UNSIGNED NOT NULL,PRIMARY KEY (`api_name`),UNIQUE KEY `module_api` (`module_directory`,`api_name`))");
        if(!self::querySucceeded($result))throw new RuntimeException(wbce_api_t('database_unavailable'));
        self::$schemaReady=true;
    }

    public static function register($database,$name,$module,$handler)
    {
        if (!is_object($database) || !method_exists($database, 'query')) throw new RuntimeException(wbce_api_t('database_unavailable'));
        $name=strtolower(trim((string)$name));$module=strtolower(trim((string)$module));$handler=trim(str_replace('\\','/',(string)$handler),'/');
        if(!preg_match('/^[a-z][a-z0-9_-]{0,63}$/',$name)||!preg_match('/^[a-z][a-z0-9_-]{1,189}$/',$module)||$handler===''||strpos($handler,'..')!==false)throw new InvalidArgumentException(wbce_api_t('invalid_registration'));
        $moduleRoot=realpath(WB_PATH.'/modules/'.$module);$target=realpath(WB_PATH.'/modules/'.$module.'/'.$handler);
        if(!$moduleRoot||!$target||!is_file($target)||strpos($target,$moduleRoot.DIRECTORY_SEPARATOR)!==0)throw new RuntimeException(wbce_api_t('handler_missing'));
        $existingResult=$database->query("SELECT `module_directory` FROM `".self::table()."` WHERE `api_name`='".$database->escapeString($name)."' LIMIT 1");
        if(!self::querySucceeded($existingResult))throw new RuntimeException(wbce_api_t('registration_failed'));
        $existing=self::row($existingResult);
        if($existing&&strtolower((string)$existing['module_directory'])!==$module)throw new RuntimeException(wbce_api_t('route_conflict'));
        $result=$database->query("INSERT INTO `".self::table()."` (`api_name`,`module_directory`,`handler`,`created_at`) VALUES ('".$database->escapeString($name)."','".$database->escapeString($module)."','".$database->escapeString($handler)."',".time().") ON DUPLICATE KEY UPDATE `module_directory`=VALUES(`module_directory`),`handler`=VALUES(`handler`)");
        if(!self::querySucceeded($result))throw new RuntimeException(wbce_api_t('registration_failed'));
    }

    public static function unregisterModule($database,$module)
    {
        if (!is_object($database) || !method_exists($database, 'query')) return false;
        $result=$database->query("DELETE FROM `".self::table()."` WHERE `module_directory`='".$database->escapeString(strtolower(trim((string)$module)))."'");
        return self::querySucceeded($result);
    }

    public static function routes($database)
    {
        if (!is_object($database) || !method_exists($database, 'query')) throw new RuntimeException(wbce_api_t('database_unavailable'));
        $routes=array();$result=$database->query("SELECT * FROM `".self::table()."` ORDER BY `api_name`");if(!self::querySucceeded($result))throw new RuntimeException(wbce_api_t('database_unavailable'));while($row=self::row($result))$routes[]=$row;return $routes;
    }

    public static function count($database)
    {
        if (!is_object($database) || !method_exists($database, 'query')) return 0;
        $result=$database->query("SELECT COUNT(*) AS total FROM `".self::table()."`");if(!self::querySucceeded($result))throw new RuntimeException(wbce_api_t('database_unavailable'));$row=self::row($result);return $row?(int)$row['total']:0;
    }

    public static function isRegistered($database,$name,$module='')
    {
        if (!is_object($database) || !method_exists($database, 'query')) return false;
        $where="`api_name`='".$database->escapeString(strtolower(trim((string)$name)))."'";if($module!=='')$where.=" AND `module_directory`='".$database->escapeString(strtolower(trim((string)$module)))."'";$result=$database->query("SELECT `api_name` FROM `".self::table()."` WHERE $where LIMIT 1");if(!self::querySucceeded($result))throw new RuntimeException(wbce_api_t('database_unavailable'));return (bool)self::row($result);
    }

    public static function url($name)
    {
        return rtrim(WB_URL,'/').'/modules/api/'.rawurlencode((string)$name).'/';
    }

    public static function denyLegacyIfRegistered($database,$name,$module)
    {
        if(defined('WBCE_API_DISPATCHED')&&WBCE_API_DISPATCHED)return;
        try {$registered=self::isRegistered($database,$name,$module);}catch(Throwable $error){self::jsonError('api_unavailable',wbce_api_t('api_unavailable'),503);}
        if($registered){http_response_code(404);header('Content-Type: application/json; charset=UTF-8');header('Cache-Control: no-store');echo json_encode(array('error'=>'api_moved','message'=>wbce_api_t('legacy_unavailable')),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);exit;}
    }

    public static function dispatch($database,$name,$path='')
    {
        if (!is_object($database) || !method_exists($database, 'query')) self::jsonError('api_unavailable',wbce_api_t('api_unavailable'),503);
        if(!preg_match('/^[a-z][a-z0-9_-]{0,63}$/',(string)$name))self::jsonError('api_not_found',wbce_api_t('api_not_found'),404);
        $result=$database->query("SELECT * FROM `".self::table()."` WHERE `api_name`='".$database->escapeString((string)$name)."' LIMIT 1");if(!self::querySucceeded($result))self::jsonError('api_unavailable',wbce_api_t('api_unavailable'),503);$route=self::row($result);
        if(!$route)self::jsonError('api_not_found',wbce_api_t('api_not_found'),404);
        $moduleRoot=realpath(WB_PATH.'/modules/'.$route['module_directory']);$target=realpath(WB_PATH.'/modules/'.$route['module_directory'].'/'.$route['handler']);
        if(!$moduleRoot||!$target||!is_file($target)||strpos($target,$moduleRoot.DIRECTORY_SEPARATOR)!==0)self::jsonError('api_unavailable',wbce_api_t('api_unavailable'),503);
        header('Cache-Control: no-store');header('X-Content-Type-Options: nosniff');
        if(!defined('WBCE_API_DISPATCHED'))define('WBCE_API_DISPATCHED',true);if(!defined('WBCE_API_NAME'))define('WBCE_API_NAME',(string)$name);if(!defined('WBCE_API_PATH'))define('WBCE_API_PATH',trim((string)$path,'/'));
        require $target;
    }

    private static function jsonError($code,$message,$status=400)
    {
        http_response_code((int)$status);
        header('Content-Type: application/json; charset=UTF-8');
        header('Cache-Control: no-store');
        header('X-Content-Type-Options: nosniff');
        $json=json_encode(array('error'=>(string)$code,'message'=>(string)$message),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        echo is_string($json)?$json:'{"error":"api_unavailable"}';
        exit;
    }
}
