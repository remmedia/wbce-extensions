<?php
ob_start();
function sfs_reply($ok,$message,$status=200,array $extra=array()){http_response_code((int)$status);header('Content-Type: application/json; charset=UTF-8');header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');header('X-Content-Type-Options: nosniff');$json=json_encode(array_merge(array('success'=>(bool)$ok,'message'=>(string)$message),$extra),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT);echo is_string($json)?$json:'{"success":false}';exit;}
$language=strtoupper(substr((string)($_SERVER['HTTP_ACCEPT_LANGUAGE']??'EN'),0,2));if(!preg_match('/^[A-Z]{2}$/D',$language))$language='EN';require __DIR__.'/languages/EN.php';$fallback=$SFS;if($language!=='EN'&&is_readable(__DIR__.'/languages/'.$language.'.php'))require __DIR__.'/languages/'.$language.'.php';$SFS=array_merge($fallback,is_array($SFS)?$SFS:array());
$config=dirname(__DIR__,2).'/config.php';if(!is_readable($config)){if(ob_get_level())ob_end_clean();sfs_reply(false,$SFS['CONFIGURATION_UNAVAILABLE'],500);}require_once $config;require_once WB_PATH.'/framework/Admin.php';
$language=defined('LANGUAGE')?strtoupper((string)LANGUAGE):'EN';require __DIR__.'/languages/EN.php';$fallback=$SFS;if($language!=='EN'&&preg_match('/^[A-Z]{2}$/D',$language)&&is_readable(__DIR__.'/languages/'.$language.'.php'))require __DIR__.'/languages/'.$language.'.php';$SFS=array_merge($fallback,is_array($SFS)?$SFS:array());
$admin=new admin('admintools','admintools',false);if(ob_get_level())ob_end_clean();if(!$admin->is_authenticated()||!$admin->get_permission('SecureFormSwitcher','module'))sfs_reply(false,$SFS['FORBIDDEN'],403);$requestMethod=isset($_SERVER['REQUEST_METHOD'])&&is_string($_SERVER['REQUEST_METHOD'])?$_SERVER['REQUEST_METHOD']:'GET';if($requestMethod!=='POST'||!$admin->checkFTAN())sfs_reply(false,$SFS['SECURITY_ERROR'],403,array('ftan'=>$admin->getFTAN()));
$scalar=static function($key,$default=''){return isset($_POST[$key])&&is_scalar($_POST[$key])?(string)$_POST[$key]:(string)$default;};
$action=$scalar('action','save');if(!in_array($action,array('save','defaults'),true))sfs_reply(false,$SFS['INVALID_SETTINGS'],422,array('ftan'=>$admin->getFTAN()));
if($action==='defaults'){try{$generatedSecret=bin2hex(random_bytes(24));}catch(Throwable $error){sfs_reply(false,$SFS['RANDOM_FAILED'],500,array('ftan'=>$admin->getFTAN()));}$v=array('useFP'=>false,'ipOctets'=>'2','tokenName'=>'formtoken','timeout'=>'7200','secret'=>$generatedSecret,'secretTime'=>'86400');}else{$v=array('useFP'=>in_array($scalar('useFP','0'),array('1','true'),true),'ipOctets'=>$scalar('ipOctets'),'tokenName'=>$scalar('tokenName'),'timeout'=>$scalar('timeout'),'secret'=>$scalar('secret'),'secretTime'=>$scalar('secretTime'));if(!preg_match('/^[0-4]$/D',$v['ipOctets'])||!preg_match('/^[a-zA-Z]{5,20}$/D',$v['tokenName'])||!preg_match('/^[0-9]{1,5}$/D',$v['timeout'])||!preg_match('/^[a-zA-Z0-9]{20,60}$/D',$v['secret'])||!preg_match('/^[0-9]{1,5}$/D',$v['secretTime']))sfs_reply(false,$SFS['INVALID_SETTINGS'],422,array('ftan'=>$admin->getFTAN()));}
$map=array('wb_secform_usefp'=>$v['useFP'],'fingerprint_with_ip_octets'=>$v['ipOctets'],'wb_secform_tokenname'=>$v['tokenName'],'wb_secform_timeout'=>$v['timeout'],'wb_session_timeout'=>$v['timeout'],'wb_secform_secret'=>$v['secret'],'wb_secform_secrettime'=>$v['secretTime']);
$errors=array();
foreach($map as $name=>$value){
    $result=Settings::Set($name,$value);
    if($result!==false&&$result!==null&&$result!=='')$errors[]=(string)$result;
}
if($errors)sfs_reply(false,$SFS['SAVE_FAILED'],500,array('ftan'=>$admin->getFTAN()));
$stored=array(
    'useFP'=>(bool)Settings::Get('wb_secform_usefp'),
    'ipOctets'=>(string)Settings::Get('fingerprint_with_ip_octets'),
    'tokenName'=>(string)Settings::Get('wb_secform_tokenname'),
    'timeout'=>(string)Settings::Get('wb_secform_timeout'),
    'secret'=>(string)Settings::Get('wb_secform_secret'),
    'secretTime'=>(string)Settings::Get('wb_secform_secrettime')
);
if ($stored['useFP'] !== (bool)$v['useFP'] || $stored['ipOctets'] !== (string)$v['ipOctets'] || $stored['tokenName'] !== (string)$v['tokenName'] || $stored['timeout'] !== (string)$v['timeout'] || $stored['secret'] !== (string)$v['secret'] || $stored['secretTime'] !== (string)$v['secretTime']) sfs_reply(false,$SFS['SAVE_NOT_CONFIRMED'],500,array('ftan'=>$admin->getFTAN()));
$publicSettings=$stored;unset($publicSettings['secret']);if($action==='defaults')$publicSettings['secret']=$stored['secret'];
sfs_reply(true,$action==='defaults'?$SFS['DEFAULTS_SAVED']:$SFS['SAVED'],200,array('settings'=>$publicSettings,'ftan'=>$admin->getFTAN()));
