<?php
/** Hook-bridge registry. WBCE 1.7 supplies the native implementation. */
if (!class_exists('WbceAuthenticationProviderManager')) {
final class WbceAuthenticationProviderManager {
    private static $providers = array();
    public static function register(WbceAuthenticationProviderInterface $provider) { $id=(string)$provider->getId(); if(!preg_match('/^[a-z][a-z0-9_.-]{1,63}$/',$id)) throw new InvalidArgumentException('Invalid authentication provider id'); self::$providers[$id]=$provider; if(function_exists('wbce_do_action'))wbce_do_action('auth.provider.registered',$id,$provider); }
    public static function providers() { $items=array('wbce'=>array('id'=>'wbce','name'=>'WBCE','protected'=>true)); foreach(self::$providers as $id=>$provider)$items[$id]=array('id'=>$id,'name'=>(string)$provider->getName(),'protected'=>false); return function_exists('wbce_apply_array_filters')?wbce_apply_array_filters('auth.login.providers',$items):$items; }
    public static function policy() { $value=class_exists('Settings')?Settings::get('auth_login_providers',array()):array(); if(!is_array($value))$value=array(); $value+=array('providers'=>array('wbce'=>array('enabled'=>true,'scope'=>'all')),'users'=>array()); return $value; }
}
}
