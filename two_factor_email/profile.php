<?php
require_once '../../config.php';
require_once WB_PATH.'/framework/Admin.php';
require_once WB_PATH.'/modules/two_factor/Language.php';
require_once WB_PATH.'/modules/two_factor/Settings.php';
require_once WB_PATH.'/modules/two_factor/EmbeddedView.php';
require_once __DIR__.'/Service.php';
$admin=new admin('Preferences','start',false);$service=new WbceEmailFactorService($database);
if(!WbceTwoFactorSettings::enabled($database)||!WbceTwoFactorSettings::providerEnabled($database,'email')||!$service->isConfigured()){header('Location: '.ADMIN_URL.'/preferences/');exit;}
$uid=(int)$admin->get_user_id();$message='';$error='';
if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
 if(!$admin->checkFTAN())$error=wbce_two_factor_t('security_expired',array(),'two_factor_email');
 else try{$action=isset($_POST['action'])&&is_scalar($_POST['action'])?(string)$_POST['action']:'';if($action==='start'){$service->sendCode($uid,true);$message=wbce_two_factor_t('sent',array(),'two_factor_email');}elseif($action==='confirm'){if(!$service->verify($uid,isset($_POST['code'])&&is_scalar($_POST['code'])?(string)$_POST['code']:'',true))throw new RuntimeException(wbce_two_factor_t('invalid_code',array(),'two_factor_email'));$message=wbce_two_factor_t('enabled',array(),'two_factor_email');}else throw new RuntimeException(wbce_two_factor_t('unknown_action',array(),'two_factor_email'));}catch(Throwable $exception){$error=$exception->getMessage();}
}
function emf_h($value){return htmlspecialchars((string)$value,ENT_QUOTES,'UTF-8');}
$enabled=$service->isEnabled($uid);$embedded=WbceTwoFactorEmbeddedView::start();if(!$embedded)$admin->print_header();
?>
<link rel="stylesheet" href="<?php echo emf_h(WB_URL.'/modules/two_factor/admin-ui.css?v=1.1.34');?>">
<div class="wbce-admin-tool-shell tf-provider-profile emf-profile">
 <section class="content-box wbce-admin-card tf-provider-intro"><h2><?php echo emf_h(wbce_two_factor_t('preferences_title',array(),'two_factor_email'));?></h2><p><?php echo emf_h(wbce_two_factor_t('profile_intro',array(),'two_factor_email'));?></p></section>
 <?php if($message):?><div class="success" role="status"><?php echo emf_h($message);?></div><?php endif;?><?php if($error):?><div class="error" role="alert"><?php echo emf_h($error);?></div><?php endif;?>
 <?php if(!$enabled):?>
 <form method="post" class="content-box wbce-admin-card tf-provider-form"><?php echo $admin->getFTAN();?><input type="hidden" name="action" value="start"><p><?php echo emf_h(wbce_two_factor_t('saved_email',array('email'=>$service->maskedEmail($uid)),'two_factor_email'));?></p><button type="submit" class="button wbce-admin-button"><?php echo emf_h(wbce_two_factor_t('send_code',array(),'two_factor_email'));?></button></form>
 <form method="post" class="content-box wbce-admin-card tf-provider-form"><?php echo $admin->getFTAN();?><input type="hidden" name="action" value="confirm"><label class="tf-code-field"><?php echo emf_h(wbce_two_factor_t('code',array(),'two_factor_email'));?><input class="form-control" name="code" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" required></label><button type="submit" class="button wbce-admin-button"><?php echo emf_h(wbce_two_factor_t('confirm_enable',array(),'two_factor_email'));?></button></form>
 <?php else:?><section class="content-box wbce-admin-card tf-provider-active"><strong><?php echo emf_h(wbce_two_factor_t('is_active',array(),'two_factor_email'));?></strong><p><?php echo emf_h(wbce_two_factor_t('saved_email',array('email'=>$service->maskedEmail($uid)),'two_factor_email'));?></p><p><?php echo emf_h(wbce_two_factor_t('disable_centrally',array(),'two_factor_email'));?></p></section><?php endif;?>
 <p><a class="button" href="<?php echo ADMIN_URL;?>/preferences/"><?php echo emf_h(wbce_two_factor_t('back',array(),'two_factor_email'));?></a></p>
</div>
<?php if($embedded)WbceTwoFactorEmbeddedView::end();else $admin->print_footer();
