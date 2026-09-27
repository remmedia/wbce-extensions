<?php
require_once __DIR__.'/Service.php';
require_once WB_PATH.'/modules/two_factor/Language.php';
final class WbceWhatsAppFactorProvider implements WbceAuthFactorProviderInterface
{
 private $service;public function __construct($db){$this->service=new WbceWhatsAppFactorService($db);}public function getId(){return 'whatsapp';}
 public function isRequired(array $user){global $database;require_once WB_PATH.'/modules/two_factor/Settings.php';return WbceTwoFactorSettings::enabled($database)&&WbceTwoFactorSettings::userProvider($database,(int)$user['user_id'])==='whatsapp'&&$this->service->isEnabled($user['user_id']);}
 public function renderChallenge(array $user,$error=''){try{$this->service->sendCode($user['user_id']);}catch(Throwable $e){$error=$e->getMessage();}require_once WB_PATH.'/modules/two_factor/ChallengeView.php';return WbceTwoFactorChallengeView::render('whatsapp',wbce_two_factor_t('challenge_description',array(),'two_factor_whatsapp'),wbce_two_factor_t('code',array(),'two_factor_whatsapp'),'<input id="whatsapp-code" name="factor_code" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" required autofocus>',wbce_two_factor_t('finish_login',array(),'two_factor_whatsapp'),$error);}
 public function verify(array $user,array $input){return $this->service->verify($user['user_id'],$input['factor_code']??'');}
}
