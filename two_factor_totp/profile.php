<?php
require_once '../../config.php';
require_once WB_PATH . '/framework/Admin.php';
require_once __DIR__ . '/Service.php';
require_once WB_PATH . '/modules/two_factor/Language.php';
require_once WB_PATH . '/modules/two_factor/EmbeddedView.php';

$admin = new admin('Preferences', 'start', false);
require_once WB_PATH . '/modules/two_factor/Settings.php';
if (!WbceTwoFactorSettings::enabled($database) || !WbceTwoFactorSettings::providerEnabled($database, 'totp')) {
    header('Location: ' . ADMIN_URL . '/preferences/');
    exit;
}
$service = new WbceTotpService($database);
$userId = (int)$admin->get_user_id();
$embeddedRequested = WbceTwoFactorEmbeddedView::requested();
$message = '';
$error = '';
$recoveryCodes = array();

if (($_SERVER['REQUEST_METHOD']??'GET') === 'POST') {
    if (!$admin->checkFTAN()) {
        $error = wbce_two_factor_t('security_failed',array(),'two_factor_totp');
    } else {
        $action = isset($_POST['action'])&&is_scalar($_POST['action']) ? (string)$_POST['action'] : '';
        $password = isset($_POST['current_password'])&&is_scalar($_POST['current_password']) ? (string)$_POST['current_password'] : '';
        if ($action === 'backup_done') {
            unset($_SESSION['TOTP_NEW_RECOVERY_CODES']);
            header('Location: ' . WB_URL . '/modules/two_factor_totp/profile.php' . ($embeddedRequested ? '?embedded=1' : ''));
            exit;
        } elseif (!$admin->doCheckPassword($userId, $password)) {
            $error = wbce_two_factor_t('password_invalid',array(),'two_factor_totp');
        } elseif ($action === 'start') {
            $_SESSION['TOTP_SETUP_SECRET'] = WbceTotp::generateSecret();
        } elseif ($action === 'confirm' && !empty($_SESSION['TOTP_SETUP_SECRET'])) {
            $counter = null;
            if (WbceTotp::verify($_SESSION['TOTP_SETUP_SECRET'], isset($_POST['code'])&&is_scalar($_POST['code']) ? (string)$_POST['code'] : '', null, 1, $counter)) {
                try {
                    $recoveryCodes = $service->enable($userId, $_SESSION['TOTP_SETUP_SECRET']);
                    WbceTwoFactorSettings::setUserProvider($database,$userId,'totp');
                    if (!$service->isEnabled($userId)) {
                        throw new RuntimeException(wbce_two_factor_t('state_failed',array(),'two_factor_totp'));
                    }
                    unset($_SESSION['TOTP_SETUP_SECRET']);
                    $_SESSION['TOTP_NEW_RECOVERY_CODES'] = $recoveryCodes;
                    wbce_do_action('two_factor.enabled', $userId);
                    header('Location: ' . WB_URL . '/modules/two_factor_totp/profile.php?view=backup' . ($embeddedRequested ? '&embedded=1' : ''));
                    exit;
                } catch (Throwable $exception) {
                    $error = $exception->getMessage();
                }
            } else {
                $error = wbce_two_factor_t('confirmation_invalid',array(),'two_factor_totp');
            }
        } elseif ($action === 'recovery' && $service->isEnabled($userId)) {
            $recoveryCodes = $service->replaceRecoveryCodes($userId);
            $_SESSION['TOTP_NEW_RECOVERY_CODES'] = $recoveryCodes;
            header('Location: ' . WB_URL . '/modules/two_factor_totp/profile.php?view=backup' . ($embeddedRequested ? '&embedded=1' : ''));
            exit;
        } elseif ($action === 'disable' && $service->isEnabled($userId)) {
            $code = isset($_POST['code'])&&is_scalar($_POST['code']) ? (string)$_POST['code'] : '';
            if ($service->verify($userId, $code)) {
                $service->disable($userId);
                if(WbceTwoFactorSettings::userProvider($database,$userId)==='totp')WbceTwoFactorSettings::setUserProvider($database,$userId,'');
                $message = wbce_two_factor_t('disabled_message',array(),'two_factor_totp');
                wbce_do_action('two_factor.disabled', $userId);
            } else {
                $error = wbce_two_factor_t('code_invalid',array(),'two_factor_totp');
            }
        }
    }
}

$enabled = $service->isEnabled($userId);
$showBackupPage = isset($_GET['view']) && $_GET['view'] === 'backup'
    && !empty($_SESSION['TOTP_NEW_RECOVERY_CODES'])
    && is_array($_SESSION['TOTP_NEW_RECOVERY_CODES']);
if ($showBackupPage) {
    $recoveryCodes = $_SESSION['TOTP_NEW_RECOVERY_CODES'];
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
}
$embedded=WbceTwoFactorEmbeddedView::start();if(!$embedded)$admin->print_header();
$secret = isset($_SESSION['TOTP_SETUP_SECRET']) ? $_SESSION['TOTP_SETUP_SECRET'] : '';
$issuer = rawurlencode(defined('WEBSITE_TITLE') ? WEBSITE_TITLE : 'WBCE');
$account = rawurlencode($admin->get_email());
$uri = $secret ? 'otpauth://totp/' . $issuer . ':' . $account . '?secret=' . $secret . '&issuer=' . $issuer . '&digits=6&period=30' : '';
?>
<link rel="stylesheet" href="<?php echo WB_URL; ?>/modules/two_factor/admin-ui.css?v=1.1.32">
<link rel="stylesheet" href="<?php echo WB_URL; ?>/modules/two_factor_totp/backend.css?v=2.1.18">
<div class="totp-profile tf-provider-profile">
<div class="totp-profile-intro content-box wbce-admin-card"><span class="fa fa-shield" aria-hidden="true"></span><p><?php echo htmlspecialchars(wbce_two_factor_t('intro',array(),'two_factor_totp'),ENT_QUOTES,'UTF-8');?></p></div>
<?php if ($message): ?><div class="success"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
<?php if ($error): ?><div class="error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>

<?php if ($showBackupPage): ?>
<section class="content-box wbce-admin-card totp-backup-page">
<h3><?php echo htmlspecialchars(wbce_two_factor_t('backup_title',array(),'two_factor_totp'),ENT_QUOTES,'UTF-8');?></h3>
<div class="warning"><strong><?php echo htmlspecialchars(wbce_two_factor_t('backup_once',array(),'two_factor_totp'),ENT_QUOTES,'UTF-8');?></strong><br>
<?php echo htmlspecialchars(wbce_two_factor_t('backup_hint',array(),'two_factor_totp'),ENT_QUOTES,'UTF-8');?></div>
<ol class="totp-backup-codes">
<?php foreach ($recoveryCodes as $code): ?><li><code><?php echo htmlspecialchars($code, ENT_QUOTES, 'UTF-8'); ?></code></li><?php endforeach; ?>
</ol>
<p><?php echo htmlspecialchars(wbce_two_factor_t('backup_store',array(),'two_factor_totp'),ENT_QUOTES,'UTF-8');?></p>
<form method="post"><?php echo $admin->getFTAN(); ?><input type="hidden" name="action" value="backup_done">
<button type="submit" class="button wbce-admin-button"><?php echo htmlspecialchars(wbce_two_factor_t('backup_confirm',array(),'two_factor_totp'),ENT_QUOTES,'UTF-8');?></button></form>
</section>
<?php elseif (!$enabled && !$secret): ?>
<form method="post" class="totp-panel totp-form content-box wbce-admin-card"><h3><?php echo htmlspecialchars(wbce_two_factor_t('setup_title',array(),'two_factor_totp'),ENT_QUOTES,'UTF-8');?></h3><p><?php echo htmlspecialchars(wbce_two_factor_t('confirm_password_first',array(),'two_factor_totp'),ENT_QUOTES,'UTF-8');?></p><?php echo $admin->getFTAN(); ?>
<input type="hidden" name="action" value="start">
<div class="totp-field"><label for="totp-current-password-start"><?php echo htmlspecialchars(wbce_two_factor_t('current_password',array(),'two_factor_totp'),ENT_QUOTES,'UTF-8');?></label><input class="form-control" id="totp-current-password-start" type="password" name="current_password" autocomplete="current-password" required></div>
<button type="submit" class="button wbce-admin-button"><?php echo htmlspecialchars(wbce_two_factor_t('begin',array(),'two_factor_totp'),ENT_QUOTES,'UTF-8');?></button></form>
<?php elseif (!$enabled): ?>
<section class="content-box wbce-admin-card totp-panel"><h3><?php echo htmlspecialchars(wbce_two_factor_t('connect_app',array(),'two_factor_totp'),ENT_QUOTES,'UTF-8');?></h3>
<p><?php echo htmlspecialchars(wbce_two_factor_t('scan_qr',array(),'two_factor_totp'),ENT_QUOTES,'UTF-8');?></p>
<div id="totp-qrcode" data-uri="<?php echo htmlspecialchars($uri, ENT_QUOTES, 'UTF-8'); ?>"
     role="img" aria-label="<?php echo htmlspecialchars(wbce_two_factor_t('qr_label',array(),'two_factor_totp'),ENT_QUOTES,'UTF-8');?>"></div>
<script src="<?php echo WB_URL; ?>/modules/two_factor_totp/qrcode.min.js?v=1.0.0"></script>
<script src="<?php echo WB_URL; ?>/modules/two_factor_totp/totp-setup.js?v=1.1.4"></script>
<p><?php echo htmlspecialchars(wbce_two_factor_t('manual_key',array(),'two_factor_totp'),ENT_QUOTES,'UTF-8');?></p>
<p><code><?php echo htmlspecialchars($secret, ENT_QUOTES, 'UTF-8'); ?></code></p>
<details><summary><?php echo htmlspecialchars(wbce_two_factor_t('technical_uri',array(),'two_factor_totp'),ENT_QUOTES,'UTF-8');?></summary><code class="tf-technical-value"><?php echo htmlspecialchars($uri, ENT_QUOTES, 'UTF-8'); ?></code></details>
<form method="post" class="totp-form"><?php echo $admin->getFTAN(); ?><input type="hidden" name="action" value="confirm">
<div class="totp-field"><label for="totp-current-password-confirm"><?php echo htmlspecialchars(wbce_two_factor_t('current_password',array(),'two_factor_totp'),ENT_QUOTES,'UTF-8');?></label><input class="form-control" id="totp-current-password-confirm" type="password" name="current_password" autocomplete="current-password" required></div>
<div class="totp-field totp-code-field"><label for="totp-confirm-code"><?php echo htmlspecialchars(wbce_two_factor_t('app_code',array(),'two_factor_totp'),ENT_QUOTES,'UTF-8');?></label><input class="form-control" id="totp-confirm-code" name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="6" required></div>
<button type="submit" class="button wbce-admin-button"><?php echo htmlspecialchars(wbce_two_factor_t('enable',array(),'two_factor_totp'),ENT_QUOTES,'UTF-8');?></button></form></section>
<?php else: ?>
<section class="totp-panel content-box wbce-admin-card"><div class="totp-active-status"><span aria-hidden="true">✓</span><div><strong><?php echo htmlspecialchars(wbce_two_factor_t('is_active',array(),'two_factor_totp'),ENT_QUOTES,'UTF-8');?></strong><small><?php echo htmlspecialchars(wbce_two_factor_t('active_hint',array(),'two_factor_totp'),ENT_QUOTES,'UTF-8');?></small></div></div>
<div class="totp-action-grid"><form method="post" class="totp-form"><?php echo $admin->getFTAN(); ?><input type="hidden" name="action" value="recovery">
<div class="totp-field"><label for="totp-current-password-recovery"><?php echo htmlspecialchars(wbce_two_factor_t('current_password',array(),'two_factor_totp'),ENT_QUOTES,'UTF-8');?></label><input class="form-control" id="totp-current-password-recovery" type="password" name="current_password" autocomplete="current-password" required></div>
<button type="submit" class="button wbce-admin-button"><?php echo htmlspecialchars(wbce_two_factor_t('new_backup',array(),'two_factor_totp'),ENT_QUOTES,'UTF-8');?></button></form>
<form method="post" class="totp-form totp-danger-zone"><?php echo $admin->getFTAN(); ?><input type="hidden" name="action" value="disable">
<div class="totp-field"><label for="totp-current-password-disable"><?php echo htmlspecialchars(wbce_two_factor_t('current_password',array(),'two_factor_totp'),ENT_QUOTES,'UTF-8');?></label><input class="form-control" id="totp-current-password-disable" type="password" name="current_password" autocomplete="current-password" required></div>
<div class="totp-field totp-code-field"><label for="totp-disable-code"><?php echo htmlspecialchars(wbce_two_factor_t('auth_or_backup',array(),'two_factor_totp'),ENT_QUOTES,'UTF-8');?></label><input class="form-control" id="totp-disable-code" name="code" autocomplete="one-time-code" required></div>
<button type="submit" class="button wbce-admin-button"><?php echo htmlspecialchars(wbce_two_factor_t('disable',array(),'two_factor_totp'),ENT_QUOTES,'UTF-8');?></button></form></div></section>
<?php endif; ?>
<?php if (!$showBackupPage): ?><p class="totp-back-link"><a class="button" href="<?php echo ADMIN_URL; ?>/preferences/"><span aria-hidden="true">←</span> <?php echo htmlspecialchars(wbce_two_factor_t('back',array(),'two_factor_totp'),ENT_QUOTES,'UTF-8');?></a></p><?php endif; ?>
</div>
<?php if($embedded)WbceTwoFactorEmbeddedView::end();else $admin->print_footer(); ?>
