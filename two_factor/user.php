<?php
require_once '../../config.php';
require_once WB_PATH.'/framework/Admin.php';
require_once __DIR__.'/Language.php';
require_once __DIR__.'/Registry.php';
require_once __DIR__.'/Settings.php';
$admin=new admin('Preferences','start',false);
header('Content-Type: application/json; charset=UTF-8');
function tf_user_reply($admin,array $data,$status=200){http_response_code($status);$data['ftan']=$admin->getFTAN();echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);exit;}
function tf_user_scalar($name,$default=''){return isset($_POST[$name])&&is_scalar($_POST[$name])?(string)$_POST[$name]:(string)$default;}
try{
 if(($_SERVER['REQUEST_METHOD']??'GET')!=='POST'||!$admin->checkFTAN())throw new RuntimeException(wbce_two_factor_t('security_expired'));
 $uid=(int)$admin->get_user_id();$action=tf_user_scalar('action','select');$providers=WbceTwoFactorRegistry::providers();
 if($action==='prepare_disable'||$action==='disable'){
  $current=WbceTwoFactorSettings::userProvider($database,$uid);$password=tf_user_scalar('password');
  if($current===''||!isset($providers[$current]))throw new RuntimeException(wbce_two_factor_t('provider_missing'));
  if($password===''||!$admin->doCheckPassword($uid,$password))throw new RuntimeException(wbce_two_factor_t('password_invalid'));
  $provider=$providers[$current];
  if($action==='prepare_disable'){
   if(empty($provider['disable_challenge'])||!is_callable($provider['disable_challenge']))throw new RuntimeException(wbce_two_factor_t('factor_unavailable'));
   $challenge=(array)call_user_func($provider['disable_challenge'],$uid);
   tf_user_reply($admin,array('success'=>true,'challenge'=>$challenge));
  }
  if(empty($provider['verify_disable'])||!is_callable($provider['verify_disable'])||!call_user_func($provider['verify_disable'],$uid,tf_user_scalar('factor_code')))throw new RuntimeException(wbce_two_factor_t('factor_invalid'));
  if(!WbceTwoFactorSettings::setUserProvider($database,$uid,''))throw new RuntimeException(wbce_two_factor_t('selection_failed'));
  tf_user_reply($admin,array('success'=>true,'provider'=>'','message'=>wbce_two_factor_t('deactivated')));
 }
 $id=strtolower(trim(tf_user_scalar('provider')));
 if($id==='')throw new RuntimeException(wbce_two_factor_t('confirmation_missing'));
 if(!isset($providers[$id])||!WbceTwoFactorSettings::providerEnabled($database,$id))throw new RuntimeException(wbce_two_factor_t('provider_missing'));
 if(empty($providers[$id]['configured'])||!is_callable($providers[$id]['configured'])||!call_user_func($providers[$id]['configured'],$uid))throw new RuntimeException(wbce_two_factor_t('provider_not_configured'));
 if(!WbceTwoFactorSettings::setUserProvider($database,$uid,$id))throw new RuntimeException(wbce_two_factor_t('selection_failed'));
 tf_user_reply($admin,array('success'=>true,'provider'=>$id,'message'=>wbce_two_factor_t('provider_selected',array('provider'=>$providers[$id]['name']??$id))));
}catch(Throwable $e){tf_user_reply($admin,array('success'=>false,'message'=>$e->getMessage()),422);}
