<?php
require_once __DIR__.'/Service.php';
require_once WB_PATH.'/modules/two_factor/Language.php';
final class WbceTotpProvider implements WbceAuthFactorProviderInterface
{
    private $service;
    public function __construct($database){$this->service=new WbceTotpService($database);}
    public function getId(){return 'totp';}
    public function isRequired(array $user)
    {
        global $database;
        if (defined('WB_PATH') && is_file(WB_PATH.'/modules/two_factor/Settings.php')) {
            require_once WB_PATH.'/modules/two_factor/Settings.php';
            if (!WbceTwoFactorSettings::enabled($database) || WbceTwoFactorSettings::userProvider($database,(int)$user['user_id']) !== 'totp') {
                return false;
            }
        }
        return $this->service->isEnabled($user['user_id']);
    }
    public function renderChallenge(array $user,$error='')
    {
        require_once WB_PATH.'/modules/two_factor/ChallengeView.php';
        return WbceTwoFactorChallengeView::render(
            'totp',
            wbce_two_factor_t('challenge_description',array(),'two_factor_totp'),
            wbce_two_factor_t('security_code',array(),'two_factor_totp'),
            '<input name="factor_code" inputmode="text" autocomplete="one-time-code" autocapitalize="characters" spellcheck="false" maxlength="11" required autofocus>',
            wbce_two_factor_t('finish_login',array(),'two_factor_totp'),
            $error
        );
    }
    public function verify(array $user,array $input){try{return $this->service->verify($user['user_id'],$input['factor_code']??'');}catch(Throwable $exception){return false;}}
}
