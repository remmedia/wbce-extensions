<?php
defined('WB_PATH')&&isset($admin)or die('Access denied');
require_once __DIR__.'/Language.php';require_once __DIR__.'/Registry.php';require_once __DIR__.'/Settings.php';
function tf_post_scalar($name,$default=''){return isset($_POST[$name])&&is_scalar($_POST[$name])?(string)$_POST[$name]:(string)$default;}
WbceTwoFactorSettings::install($database);$providers=WbceTwoFactorRegistry::providers();$enabled=!empty($providers)&&WbceTwoFactorSettings::enabled($database);
if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
 while(ob_get_level()>0){if(!@ob_end_clean())break;}
 header('Content-Type: application/json; charset=UTF-8');
 header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
 try{
  if(!$admin->checkFTAN())throw new RuntimeException(wbce_two_factor_t('security_expired'));
  $action=tf_post_scalar('action');
  if($action==='provider_config'){
   $id=strtolower(trim(tf_post_scalar('config_provider')));
   if(empty($providers[$id]['save_config'])||!is_callable($providers[$id]['save_config']))throw new RuntimeException(wbce_two_factor_t('provider_missing'));
   call_user_func($providers[$id]['save_config'],$_POST);
   $ready=empty($providers[$id]['admin_config'])||(!empty($providers[$id]['system_configured'])&&is_callable($providers[$id]['system_configured'])&&(bool)call_user_func($providers[$id]['system_configured']));
   echo json_encode(array('success'=>true,'provider'=>$id,'configured'=>$ready,'message'=>wbce_two_factor_t('provider_config_saved'),'ftan'=>tf_fresh_ftan($admin)));exit;
  }
  if($action==='provider_toggle'){
   $id=strtolower(trim(tf_post_scalar('provider')));if(!isset($providers[$id]))throw new RuntimeException(wbce_two_factor_t('provider_missing'));
   $value=tf_post_scalar('enabled','0')==='1';if($value&&!tf_provider_ready($providers[$id]))throw new RuntimeException(wbce_two_factor_t('admin_provider_not_configured'));if(!WbceTwoFactorSettings::setProviderEnabled($database,$id,$value))throw new RuntimeException(wbce_two_factor_t('status_save_failed'));
   echo json_encode(array('success'=>true,'provider'=>$id,'enabled'=>$value,'global_enabled'=>WbceTwoFactorSettings::enabled($database),'message'=>wbce_two_factor_t($value?'provider_enabled':'provider_disabled'),'ftan'=>tf_fresh_ftan($admin)));exit;
  }
  if($action==='toggle'){
   $value=tf_post_scalar('enabled','0')==='1';if($value&&WbceTwoFactorSettings::enabledProviderIds($database)===array())throw new RuntimeException(wbce_two_factor_t('no_enabled_provider'));
   if(!WbceTwoFactorSettings::set($database,'enabled',$value?'1':'0'))throw new RuntimeException(wbce_two_factor_t('status_save_failed'));
   echo json_encode(array('success'=>true,'enabled'=>$value,'message'=>wbce_two_factor_t($value?'enabled_message':'disabled_message'),'ftan'=>tf_fresh_ftan($admin)));exit;
  }
  throw new RuntimeException(wbce_two_factor_t('save_failed'));
 }catch(Throwable $e){http_response_code(422);echo json_encode(array('success'=>false,'message'=>$e->getMessage(),'ftan'=>tf_fresh_ftan($admin)));exit;}
}
function tf_h($value){return htmlspecialchars((string)$value,ENT_QUOTES,'UTF-8');}
function tf_fresh_ftan($admin){$value=array();parse_str((string)$admin->getFTAN(false),$value);return $value;}
function tf_provider_ready($provider){try{return empty($provider['admin_config'])||(!empty($provider['system_configured'])&&is_callable($provider['system_configured'])&&(bool)call_user_func($provider['system_configured']));}catch(Throwable $ignored){return false;}}
function tf_provider_config($provider){try{return !empty($provider['admin_config'])&&is_callable($provider['admin_config'])?(string)call_user_func($provider['admin_config']):'';}catch(Throwable $error){return '<p class="error" role="alert">'.tf_h($error->getMessage()).'</p>';}}
$tfFtan=array();parse_str((string)$admin->getFTAN(false),$tfFtan);
?>
<link rel="stylesheet" href="<?php echo tf_h(WB_URL.'/modules/two_factor/admin-ui.css?v=1.1.40');?>">
<div class="tf-admin wbce-admin-tool-shell">
 <header class="wbce-admin-hero"><i class="fa fa-shield" aria-hidden="true"></i><div class="wbce-admin-hero__content"><h2><?php echo tf_h(wbce_two_factor_t('admin_title'));?></h2><p><?php echo tf_h(wbce_two_factor_t('admin_intro'));?></p></div><label class="wbce-admin-switch tf-global"><span data-global-label><?php echo tf_h(wbce_two_factor_t($enabled?'active':'inactive'));?></span><input type="checkbox" data-global-toggle<?php echo $enabled?' checked':'';?><?php echo empty($providers)?' disabled':'';?>><i class="wbce-admin-switch__control"></i></label></header>
 <?php if(!$providers):?><section class="content-box" role="status"><h3><?php echo tf_h(wbce_two_factor_t('no_provider_title'));?></h3><p><?php echo tf_h(wbce_two_factor_t('no_provider_hint'));?></p></section><?php endif;?>
 <div class="tf-grid wbce-admin-component-grid">
 <?php foreach($providers as $id=>$provider):$usage=WbceTwoFactorRegistry::usage($id);$active=WbceTwoFactorSettings::providerEnabled($database,$id);$ready=tf_provider_ready($provider);?>
  <article class="content-box wbce-admin-card tf-card" data-card="<?php echo tf_h($id);?>">
   <header><span class="fa <?php echo tf_h($provider['icon']??'fa-shield');?>" aria-hidden="true"></span><div><h3><?php echo tf_h($provider['name']??$id);?></h3><span class="tf-status<?php echo $ready?' is-ready':'';?>"><i></i><b data-status><?php echo tf_h(wbce_two_factor_t($ready?'configured':'not_configured'));?></b></span></div></header>
   <p><?php echo tf_h($provider['description']??'');?></p><small><?php echo tf_h(wbce_two_factor_t('users_configured',array('count'=>$usage)));?></small>
   <footer><label class="wbce-admin-switch tf-provider-toggle"><span><?php echo tf_h(wbce_two_factor_t('active'));?></span><input type="checkbox" data-provider-toggle="<?php echo tf_h($id);?>"<?php echo $active?' checked':'';?><?php echo !$ready&&!$active?' disabled title="'.tf_h(wbce_two_factor_t('admin_provider_not_configured')).'"':'';?>><i class="wbce-admin-switch__control"></i></label><?php if(!empty($provider['admin_config'])):?><button type="button" class="button" data-config-button="<?php echo tf_h($id);?>"><?php echo tf_h(wbce_two_factor_t($ready?'settings':'configure'));?></button><?php endif;?></footer>
  </article>
 <?php endforeach;?>
 </div>
 <div class="tf-configs">
 <?php foreach($providers as $id=>$provider):if(empty($provider['admin_config'])||!is_callable($provider['admin_config']))continue;?>
  <section class="content-box wbce-admin-card tf-config" data-provider-config="<?php echo tf_h($id);?>" hidden><?php echo tf_provider_config($provider);?><div class="tf-config-close-row" hidden><button type="button" class="button wbce-admin-button" data-config-close><?php echo tf_h(wbce_two_factor_t('close'));?></button></div></section>
 <?php endforeach;?>
 </div>
 <div class="tf-toasts" aria-live="polite"></div>
</div>
<script>
(function(){var root=document.querySelector('.tf-admin'),ftan=<?php echo json_encode($tfFtan,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_HEX_TAG|JSON_HEX_AMP);?>,l=<?php echo json_encode(array('configured'=>wbce_two_factor_t('configured'),'not_configured'=>wbce_two_factor_t('not_configured'),'configure'=>wbce_two_factor_t('configure'),'settings'=>wbce_two_factor_t('settings'),'saving'=>wbce_two_factor_t('saving'),'save_failed'=>wbce_two_factor_t('save_failed'),'active'=>wbce_two_factor_t('active'),'inactive'=>wbce_two_factor_t('inactive')),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);?>;
function json(response){return response.text().then(function(body){var start=body.lastIndexOf('{"success"'),end=body.lastIndexOf('}')+1;if(start<0||end<=start)throw Error(l.save_failed);var result=JSON.parse(body.slice(start,end));if(result.ftan)ftan=result.ftan;if(!response.ok||!result.success)throw Error(result.message||l.save_failed);return result})}
function data(){var copy=new FormData();Object.keys(ftan).forEach(function(key){copy.append(key,ftan[key])});return copy}
function toast(message,error){var node=document.createElement('div');node.className='tf-toast '+(error?'error':'success');node.textContent=message;root.querySelector('.tf-toasts').appendChild(node);setTimeout(function(){node.remove()},6000)}
var globalToggle=root.querySelector('[data-global-toggle]');globalToggle.addEventListener('change',function(){var wanted=this.checked,request=data();request.append('action','toggle');request.append('enabled',wanted?'1':'0');this.disabled=true;fetch(location.href,{method:'POST',body:request,headers:{'X-Requested-With':'XMLHttpRequest'}}).then(json).then(function(result){globalToggle.checked=result.enabled;root.querySelector('[data-global-label]').textContent=result.enabled?l.active:l.inactive;toast(result.message,false)}).catch(function(error){globalToggle.checked=!wanted;toast(error.message,true)}).then(function(){globalToggle.disabled=false})});
root.querySelectorAll('[data-provider-toggle]').forEach(function(toggle){toggle.addEventListener('change',function(){var wanted=toggle.checked,request=data();request.append('action','provider_toggle');request.append('provider',toggle.dataset.providerToggle);request.append('enabled',wanted?'1':'0');toggle.disabled=true;fetch(location.href,{method:'POST',body:request,headers:{'X-Requested-With':'XMLHttpRequest'}}).then(json).then(function(result){toggle.checked=result.enabled;if(!result.global_enabled){globalToggle.checked=false;root.querySelector('[data-global-label]').textContent=l.inactive}toast(result.message,false)}).catch(function(error){toggle.checked=!wanted;toast(error.message,true)}).then(function(){toggle.disabled=false})})});
root.querySelectorAll('[data-config-button]').forEach(function(button){button.addEventListener('click',function(){var section=root.querySelector('[data-provider-config="'+button.dataset.configButton+'"]'),open=section&&section.hidden;root.querySelectorAll('[data-provider-config]').forEach(function(item){item.hidden=true});if(open){section.hidden=false;section.scrollIntoView({behavior:'smooth',block:'start'})}})});
root.querySelectorAll('[data-config-close]').forEach(function(button){button.addEventListener('click',function(){button.closest('[data-provider-config]').hidden=true})});
root.querySelectorAll('[data-provider-config]').forEach(function(section){var form=section.querySelector('form'),save=form&&form.querySelector('[type="submit"]'),close=section.querySelector('[data-config-close]'),row=section.querySelector('.tf-config-close-row');if(!form||!save||!close)return;var actions=document.createElement('div');actions.className='tf-config-actions';actions.appendChild(close);actions.appendChild(save);form.appendChild(actions);if(row)row.remove()});
root.querySelectorAll('[data-email-language-picker]').forEach(function(picker){var form=picker.closest('form'),source=form&&form.querySelector('[data-email-language-templates]'),subject=form&&form.querySelector('[name="subject"]'),message=form&&form.querySelector('[name="message"]'),legend=form&&form.querySelector('[data-email-language-legend]'),payload={templates:{},names:{}};if(!form||!source||!subject||!message)return;try{payload=JSON.parse(source.textContent)}catch(ignore){}function showLanguage(){var code=picker.value,item=(payload.templates||{})[code]||{subject:'',message:''},name=(payload.names||{})[code]||code;subject.value=item.subject||'';message.value=item.message||'';if(legend)legend.textContent=name+' ('+code+')'}picker.addEventListener('change',showLanguage);if(window.jQuery)window.jQuery(picker).on('change select2:select',showLanguage);showLanguage()});
root.querySelectorAll('[data-provider-config] form').forEach(function(form){form.addEventListener('submit',function(event){event.preventDefault();var submit=form.querySelector('[type="submit"]'),label=submit?submit.textContent:'',request=new FormData(form);request.append('action','provider_config');request.append('config_provider',form.closest('[data-provider-config]').dataset.providerConfig);data().forEach(function(value,key){if(!request.has(key))request.append(key,value)});if(submit){submit.disabled=true;submit.textContent=l.saving}fetch(location.href,{method:'POST',body:request,headers:{'X-Requested-With':'XMLHttpRequest'}}).then(json).then(function(result){var card=root.querySelector('[data-card="'+result.provider+'"]'),status=card.querySelector('.tf-status'),button=card.querySelector('[data-config-button]'),toggle=card.querySelector('[data-provider-toggle]');status.classList.toggle('is-ready',result.configured);status.querySelector('b').textContent=result.configured?l.configured:l.not_configured;if(button)button.textContent=result.configured?l.settings:l.configure;if(toggle&&!toggle.checked)toggle.disabled=!result.configured;toast(result.message,false)}).catch(function(error){toast(error.message,true)}).then(function(){if(submit){submit.disabled=false;submit.textContent=label}})})});
root.querySelectorAll('[data-provider-config] input:not([type="hidden"]),[data-provider-config] select,[data-provider-config] textarea').forEach(function(field){field.classList.add('form-control')});root.querySelectorAll('[data-provider-config] button').forEach(function(button){button.classList.add('wbce-admin-button')});
})();
</script>
