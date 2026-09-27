<?php
defined('WB_PATH') or die('No direct access');
if(!interface_exists('WbceAuthFactorProviderInterface'))require_once __DIR__.'/compat/AuthFactorProviderInterface.php';
if(!class_exists('WbceAuthFactorManager'))require_once __DIR__.'/compat/AuthFactorManager.php';
final class WbceTwoFactorCompatibility
{
    public static function enforce()
    {
        if(!defined('WBCE_TWO_FACTOR_FALLBACK_MANAGER')||!WBCE_TWO_FACTOR_FALLBACK_MANAGER)return;
        if(headers_sent()||empty($_SESSION['USER_ID'])||!defined('ADMIN_URL')||!class_exists('WbceAuthFactorManager'))return;
        $request=(string)($_SERVER['REQUEST_URI']??'');$path=(string)parse_url($request,PHP_URL_PATH);$adminPath=rtrim((string)parse_url(ADMIN_URL,PHP_URL_PATH),'/');$factorPath=(string)parse_url(WB_URL.'/modules/two_factor/factor.php',PHP_URL_PATH);
        if(strpos($path,$adminPath.'/')!==0||$path===$factorPath||strpos($path,$adminPath.'/logout/')===0)return;
        $userId=(int)$_SESSION['USER_ID'];if(method_exists('WbceAuthFactorManager','isVerified')&&WbceAuthFactorManager::isVerified($userId))return;
        global $database;
        if(!is_object($database)||!is_callable(array($database,'query')))return;
        $result=$database->query('SELECT * FROM `{TP}users` WHERE `user_id`='.$userId.' AND `active`=1');
        $user=null;
        if(is_object($result)){
            if(is_callable(array($result,'fetchRow')))$user=$result->fetchRow(defined('MYSQLI_ASSOC')?MYSQLI_ASSOC:1);
            elseif(is_callable(array($result,'fetch_assoc')))$user=$result->fetch_assoc();
        }
        if(!is_array($user))return;
        $providers=WbceAuthFactorManager::requiredProviderIds($user);if(!$providers){if(method_exists('WbceAuthFactorManager','markVerified'))WbceAuthFactorManager::markVerified($userId);return;}
        if(!WbceAuthFactorManager::current())WbceAuthFactorManager::begin($userId,$providers,WB_URL.$request);
        header('Location: '.WB_URL.'/modules/two_factor/factor.php');exit;
    }
}
