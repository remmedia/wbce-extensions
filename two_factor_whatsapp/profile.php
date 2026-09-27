<?php
require_once '../../config.php';
require_once WB_PATH.'/framework/Admin.php';
require_once WB_PATH.'/modules/two_factor/Language.php';
require_once WB_PATH.'/modules/two_factor/Settings.php';
require_once WB_PATH.'/modules/two_factor/EmbeddedView.php';
require_once __DIR__.'/Service.php';
header('X-Frame-Options: SAMEORIGIN');header("Content-Security-Policy: frame-ancestors 'self'");header('Referrer-Policy: same-origin');

$admin=new admin('Preferences','start',false);
$service=new WbceWhatsAppFactorService($database);
if(!WbceTwoFactorSettings::enabled($database)||!WbceTwoFactorSettings::providerEnabled($database,'whatsapp')||!$service->isConfigured()){
 header('Location: '.ADMIN_URL.'/preferences/');exit;
}
$uid=(int)$admin->get_user_id();$message='';$error='';
if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
 if(!$admin->checkFTAN())$error=wbce_two_factor_t('security_expired',array(),'two_factor_whatsapp');
 else try{
  $action=isset($_POST['action'])&&is_scalar($_POST['action'])?(string)$_POST['action']:'';
  if($action==='start'){$service->stagePhone($uid,isset($_POST['phone'])&&is_scalar($_POST['phone'])?(string)$_POST['phone']:'');$service->sendCode($uid,true,true);$message=wbce_two_factor_t('sent',array(),'two_factor_whatsapp');}
  elseif($action==='confirm'){if(!$service->verify($uid,isset($_POST['code'])&&is_scalar($_POST['code'])?(string)$_POST['code']:'',true))throw new RuntimeException(wbce_two_factor_t('invalid_code',array(),'two_factor_whatsapp'));$message=wbce_two_factor_t('enabled',array(),'two_factor_whatsapp');}
  else throw new RuntimeException(wbce_two_factor_t('unknown_action',array(),'two_factor_whatsapp'));
 }catch(Throwable $e){$error=$e->getMessage();}
 if(strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH']??''))==='xmlhttprequest'){while(ob_get_level()>0&&!ob_end_clean())break;header('Content-Type: application/json; charset=UTF-8');header('Cache-Control: no-store, no-cache, must-revalidate');if($error!=='')http_response_code(422);echo json_encode(array('success'=>$error==='','message'=>$error!==''?$error:$message,'ftan'=>$admin->getFTAN()));exit;}
}
function wap_h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
$enabled=$service->isEnabled($uid);$phone=$service->phone($uid);$pendingPhone=$service->phone($uid,true);$embedded=WbceTwoFactorEmbeddedView::start();if(!$embedded)$admin->print_header();
?>
<link rel="stylesheet" href="<?php echo wap_h(WB_URL.'/modules/two_factor/admin-ui.css?v=1.1.34');?>">
<div class="wap wbce-admin-tool-shell tf-provider-profile" data-whatsapp-endpoint="<?php echo wap_h(WB_URL.'/modules/two_factor_whatsapp/profile.php'.($embedded?'?embedded=1':''));?>" data-action-failed="<?php echo wap_h(wbce_two_factor_t('save_failed',array(),'two_factor_whatsapp'));?>">
 <section class="content-box wbce-admin-card tf-provider-intro">
  <h2><?php echo wap_h(wbce_two_factor_t('preferences_title',array(),'two_factor_whatsapp'));?></h2>
  <p><?php echo wap_h(wbce_two_factor_t('profile_intro',array(),'two_factor_whatsapp'));?></p>
 </section>
 <?php if($message):?><div class="success"><?php echo wap_h($message);?></div><?php endif;?>
 <?php if($error):?><div class="error"><?php echo wap_h($error);?></div><?php endif;?>
 <?php if(!$enabled):?>
  <form method="post" class="content-box wbce-admin-card tf-provider-form"><?php echo $admin->getFTAN();?><input type="hidden" name="action" value="start">
   <label><?php echo wap_h(wbce_two_factor_t('phone',array(),'two_factor_whatsapp'));?><input class="form-control" name="phone" inputmode="tel" autocomplete="tel" placeholder="491701234567" value="<?php echo wap_h($pendingPhone!==''?$pendingPhone:$phone);?>" required></label>
   <button type="submit" class="button wbce-admin-button"><?php echo wap_h(wbce_two_factor_t('send_code',array(),'two_factor_whatsapp'));?></button>
  </form>
  <?php if($pendingPhone!==''):?><form method="post" class="content-box wbce-admin-card tf-provider-form"><?php echo $admin->getFTAN();?><input type="hidden" name="action" value="confirm">
   <label class="tf-code-field"><?php echo wap_h(wbce_two_factor_t('code',array(),'two_factor_whatsapp'));?><input class="form-control" name="code" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" required></label>
   <button type="submit" class="button wbce-admin-button"><?php echo wap_h(wbce_two_factor_t('confirm_enable',array(),'two_factor_whatsapp'));?></button>
  </form><?php endif;?>
 <?php else:?>
  <section class="content-box wbce-admin-card tf-provider-active"><strong><?php echo wap_h(wbce_two_factor_t('is_active',array(),'two_factor_whatsapp'));?></strong><p><?php echo wap_h(wbce_two_factor_t('saved_phone',array('phone'=>'+'.$phone),'two_factor_whatsapp'));?></p><p><?php echo wap_h(wbce_two_factor_t('disable_centrally',array(),'two_factor_whatsapp'));?></p></section>
 <?php endif;?>
 <?php if(!$embedded):?><p><a class="button" href="<?php echo ADMIN_URL;?>/preferences/"><?php echo wap_h(wbce_two_factor_t('back',array(),'two_factor_whatsapp'));?></a></p><?php endif;?>
</div>
<script src="<?php echo wap_h(WB_URL.'/modules/two_factor_whatsapp/profile.js?v=1.1.15');?>"></script>
<?php if($embedded)WbceTwoFactorEmbeddedView::end();else $admin->print_footer();
