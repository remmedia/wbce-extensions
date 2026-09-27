<?php
require_once __DIR__.'/Service.php';
require_once WB_PATH.'/modules/two_factor/Language.php';
final class WbceEmailFactorProvider implements WbceAuthFactorProviderInterface
{
 private $service;
 public function __construct($database){$this->service=new WbceEmailFactorService($database);}
 public function getId(){return 'email';}
 public function isRequired(array $user){global $database;require_once WB_PATH.'/modules/two_factor/Settings.php';return WbceTwoFactorSettings::enabled($database)&&WbceTwoFactorSettings::userProvider($database,(int)$user['user_id'])==='email'&&$this->service->isEnabled($user['user_id']);}
 public function renderChallenge(array $user,$error=''){try{$this->service->sendCode((int)$user['user_id']);}catch(Throwable $exception){$error=$exception->getMessage();}require_once WB_PATH.'/modules/two_factor/ChallengeView.php';return WbceTwoFactorChallengeView::render('email',wbce_two_factor_t('challenge_description',array(),'two_factor_email'),wbce_two_factor_t('code',array(),'two_factor_email'),'<input id="email-code" name="factor_code" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" required autofocus>',wbce_two_factor_t('finish_login',array(),'two_factor_email'),$error);}
 public function verify(array $user,array $input){return $this->service->verify((int)$user['user_id'],$input['factor_code']??'');}
}
