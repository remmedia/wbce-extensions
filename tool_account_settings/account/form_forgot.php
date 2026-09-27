<?php
/**
 * WBCE CMS
 * Way Better Content Editing.
 * Visit https://wbce.org to learn more and to join the community.
 *
 * @copyright Ryan Djurovich (2004-2009)
 * @copyright WebsiteBaker Org. e.V. (2009-2015)
 * @copyright WBCE Project (since 2015)
 * @license GNU GPL2 (or any later version)
 */

defined('WB_PATH') or die("Cannot access this file directly");
require_once(WB_PATH.'/include/captcha/captcha.php');

$oAccounts = new Accounts();
$oMsgBox   = new MessageBox();

$sLC       = defined('LANGUAGE') ? LANGUAGE : (defined('DEFAULT_LANGUAGE') ? DEFAULT_LANGUAGE : 'EN');
$sEmail    = '';

if(isset($_POST['email']) && $_POST['email'] != "" ) {
    $sEmail = strip_tags($oAccounts->get_post('email'));
    
    if (wbce_captcha_is_enabled('password_reset') && !wbce_captcha_verify(null, 'accountforgot', array('purpose' => 'password_reset'))) {
        $oMsgBox->error($MESSAGE['MOD_FORM_INCORRECT_CAPTCHA']);
        $sEmail = '';
    }
    
    if ($sEmail != '') {
        $resetService = new WbcePasswordResetService($database);
        $issued = $admin->validate_email($sEmail) ? $resetService->issue($sEmail) : null;
        if ($issued) {
            $resetUrl = WB_URL . '/account/reset-password.php?token=' . rawurlencode($issued['token']);
            $subject = sprintf(isset($TOOL_TXT['RESET_MAIL_SUBJECT']) ? $TOOL_TXT['RESET_MAIL_SUBJECT'] : 'Reset password for %s', WEBSITE_TITLE);
            $message = sprintf(
                isset($TOOL_TXT['RESET_MAIL_BODY']) ? $TOOL_TXT['RESET_MAIL_BODY'] : "Hello %s,\n\nUse this link within one hour to choose a new password:\n\n%s\n\nIf you did not request this, you can ignore this email.",
                (string)$issued['user']['display_name'],
                $resetUrl
            );
            if (!$oAccounts->mail(SERVER_EMAIL, $issued['user']['email'], $subject, $message)) {
                $resetService->revoke($issued['selector']);
            }
        }
        $oMsgBox->info(isset($TOOL_TXT['RESET_NEUTRAL']) ? $TOOL_TXT['RESET_NEUTRAL'] : 'If an active account belongs to this address, a reset link was sent.');
        $sEmail = '';
    }
}
if($oMsgBox->hasErrors() == false && !isset($_POST['email'])) {
    $oMsgBox->info($MESSAGE['FORGOT_PASS_NO_DATA'], 0, 1);
}
$email = $sEmail;
$sHttpReferer = isset($_SESSION['HTTP_REFERER']) ? $_SESSION['HTTP_REFERER'] : $_SERVER['SCRIPT_NAME'];
$captcha = '';
if (wbce_captcha_is_enabled('password_reset')) {
    ob_start();
    call_captcha("all","","accountforgot");
    $captcha = ob_get_contents();
    ob_end_clean();
}

// Get the template file for forgot_login_details
$aToTwig = array(
    'EMAIL'         => $email,
    'CAPTCHA'       => $captcha,
    'MESSAGE_BOX'   => $oMsgBox->fetchDisplay(),
);
$oAccounts->useTwigTemplate('form_forgot.twig', $aToTwig);
