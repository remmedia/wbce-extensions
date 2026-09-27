<?php
final class WbceTwoFactorChallengeView
{
    public static function render($providerId,$description,$fieldLabel,$fieldHtml,$buttonLabel,$error='')
    {
        $errorHtml=$error===''?'':'<div class="error wbce-admin-toast wbce-admin-toast--error" role="alert">'.htmlspecialchars($error,ENT_QUOTES,'UTF-8').'</div>';
        $submitHtml=$buttonLabel===''?'':'<p class="login-submit"><button type="submit" name="submit" class="button btn btn-primary wbce-admin-button">'.htmlspecialchars($buttonLabel,ENT_QUOTES,'UTF-8').'</button></p>';
        return '<div class="wbce-auth-factor wbce-auth-factor--'.htmlspecialchars($providerId,ENT_QUOTES,'UTF-8').'">'.$errorHtml.'<p class="wbce-admin-muted">'.htmlspecialchars($description,ENT_QUOTES,'UTF-8').'</p><form id="wbce-auth-factor" class="login-form wbce-admin-form" method="post" autocomplete="off"><label class="wbce-admin-field" for="wbce-factor-input">'.htmlspecialchars($fieldLabel,ENT_QUOTES,'UTF-8').'<span class="page_login_fieldelement">'.str_replace('<input ','<input id="wbce-factor-input" class="form-control" ',preg_replace('/\sid="[^"]*"/','',$fieldHtml,1)).'</span></label>'.$submitHtml.'</form></div>';
    }
}
