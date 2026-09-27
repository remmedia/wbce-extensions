<?php
defined('WB_PATH') or die('No direct access');
if(!is_file(WB_PATH.'/modules/two_factor/Language.php')||!is_file(WB_PATH.'/modules/two_factor/Registry.php'))return;
require_once WB_PATH.'/modules/two_factor/Language.php';
if(is_file(WB_PATH.'/modules/two_factor/Compatibility.php'))require_once WB_PATH.'/modules/two_factor/Compatibility.php';
if(is_file(WB_PATH.'/modules/two_factor/Registry.php')){
 require_once WB_PATH.'/modules/two_factor/Registry.php';require_once __DIR__.'/Service.php';
 WbceTwoFactorRegistry::register('webauthn',array(
  'name'=>wbce_two_factor_t('provider_name',array(),'two_factor_webauthn'),'description'=>wbce_two_factor_t('provider_description',array(),'two_factor_webauthn'),'icon'=>'fa-key',
  'usage'=>static function()use($database){return(new WbceWebAuthnService($database))->usage();},'profile_url'=>WB_URL.'/modules/two_factor_webauthn/profile.php','configured'=>static function($userId)use($database){return(new WbceWebAuthnService($database))->isEnabled($userId);},'system_configured'=>static function(){return strtolower((string)parse_url(WB_URL,PHP_URL_SCHEME))==='https'||in_array(strtolower((string)parse_url(WB_URL,PHP_URL_HOST)),array('localhost','127.0.0.1','::1'),true);},
  'disable_challenge'=>static function($userId)use($database){return array('type'=>'webauthn','options'=>(new WbceWebAuthnService($database))->assertionOptions($userId));},
  'verify_disable'=>static function($userId,$value)use($database){$payload=json_decode((string)$value,true);if(!is_array($payload))return false;$service=new WbceWebAuthnService($database);$auth=WbceWebAuthnService::b64d($payload['response']['authenticatorData']??'');if($service->setting('user_verification','preferred')==='required'&&(!is_string($auth)||strlen($auth)<33||(ord($auth[32])&4)!==4))return false;return $service->verify($userId,$payload);},
  'admin_config'=>static function()use($database){$s=new WbceWebAuthnService($database);$v=$s->setting('user_verification','preferred');return '<form><h3>'.htmlspecialchars(wbce_two_factor_t('provider_name',array(),'two_factor_webauthn'),ENT_QUOTES,'UTF-8').'</h3><p class="tf-config-intro">'.htmlspecialchars(wbce_two_factor_t('config_intro',array('domain'=>$s->rpId()),'two_factor_webauthn'),ENT_QUOTES,'UTF-8').'</p><label class="wide">'.htmlspecialchars(wbce_two_factor_t('user_verification',array(),'two_factor_webauthn'),ENT_QUOTES,'UTF-8').'<select class="form-control" name="user_verification"><option value="preferred"'.($v==='preferred'?' selected':'').'>'.htmlspecialchars(wbce_two_factor_t('preferred',array(),'two_factor_webauthn'),ENT_QUOTES,'UTF-8').'</option><option value="required"'.($v==='required'?' selected':'').'>'.htmlspecialchars(wbce_two_factor_t('required',array(),'two_factor_webauthn'),ENT_QUOTES,'UTF-8').'</option></select></label><button type="submit" class="button wbce-admin-button">'.htmlspecialchars(wbce_two_factor_t('save',array(),'two_factor_webauthn'),ENT_QUOTES,'UTF-8').'</button></form>';},
  'save_config'=>static function($input)use($database){$v=(string)($input['user_verification']??'preferred');if(!in_array($v,array('preferred','required'),true))throw new RuntimeException(wbce_two_factor_t('invalid_selection',array(),'two_factor_webauthn'));(new WbceWebAuthnService($database))->setSetting('user_verification',$v);}
 ));
}
if(!function_exists('wbce_add_filter')||!class_exists('WbceAuthFactorManager'))return;
wbce_add_filter('admin.assets',function($assets){$assets[]=array('type'=>'css','url'=>WB_URL.'/modules/two_factor_webauthn/theme.css?v=1.1.17','id'=>'two-factor-webauthn-theme');return $assets;},10);
require_once __DIR__.'/Provider.php';WbceAuthFactorManager::register(new WbceWebAuthnProvider($database));
if(class_exists('WbceTwoFactorCompatibility'))WbceTwoFactorCompatibility::enforce();
