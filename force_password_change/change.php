<?php
require_once '../../config.php';
require_once WB_PATH . '/framework/Admin.php';
require_once __DIR__ . '/Service.php';
require_once __DIR__ . '/Language.php';

$admin = new admin('Preferences', 'start', false);
$service = new WbceForcePasswordChangeService($database);
$userId = (int) $admin->get_user_id();
$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH'])
    && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
if ($isAjax) header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
if ($userId < 1 || !$service->isRequired($userId)) {
    header('Location: ' . ADMIN_URL . '/start/index.php');
    exit;
}

$error = '';
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$admin->checkFTAN()) {
        $error = fpc_t('security_failed');
    } else {
        $password = isset($_POST['new_password']) ? (string) $_POST['new_password'] : '';
        $confirmation = isset($_POST['new_password_confirmation']) ? (string) $_POST['new_password_confirmation'] : '';
        if ($password === '' || $confirmation === '') {
            $error = fpc_t('fill_both');
        } elseif ($password !== $confirmation) {
            $error = fpc_t('password_mismatch');
        } elseif (method_exists($admin, 'doCheckPassword') && $admin->doCheckPassword($userId, $password)) {
            $error = fpc_t('password_unchanged');
        } else {
            $encoded = method_exists($admin, 'checkPasswordPattern')
                ? $admin->checkPasswordPattern($password, $confirmation)
                : (function_exists('password_hash') ? password_hash($password, PASSWORD_DEFAULT) : '');
            if (is_array($encoded)) {
                $error = implode('<br>', array_map('htmlspecialchars', $encoded));
            } elseif (!is_string($encoded) || $encoded === '') {
                $error = fpc_t('password_requirements');
            } elseif (!$service->replacePasswordAndClear($userId, $encoded)) {
                $error = fpc_t('password_save_failed');
            } else {
                WbceForcePasswordChangeCompatibility::revokeOtherSessions($database, $userId);
                if (function_exists('wbce_do_action')) {
                    wbce_do_action('user.password.changed', $userId, 'forced_change');
                    wbce_do_action('user.password.forced_change_completed', $userId);
                }
                $target = isset($_SESSION['FORCE_PASSWORD_CHANGE_RETURN']) ? $_SESSION['FORCE_PASSWORD_CHANGE_RETURN'] : ADMIN_URL . '/start/index.php';
                unset($_SESSION['FORCE_PASSWORD_CHANGE_RETURN']);
                session_regenerate_id(true);
                $target = WbceForcePasswordChangeCompatibility::safeRedirect($target, ADMIN_URL . '/start/index.php');
                if ($isAjax) {
                    header('Content-Type: application/json; charset=UTF-8');
                    echo json_encode(array('ok' => true, 'redirect' => $target), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
                    exit;
                }
                header('Location: ' . $target);
                exit;
            }
        }
    }
}

if ($isAjax && $error !== '') {
    http_response_code($error === fpc_t('security_failed') ? 403 : 422);
    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    echo json_encode(array('ok' => false, 'message' => trim(preg_replace('/\s+/', ' ', strip_tags(str_replace('<br>', ' ', $error)))), 'ftan' => $admin->getFTAN()), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    exit;
}

$admin->print_header();
?>
<link rel="stylesheet" href="<?php echo htmlspecialchars(WB_URL . '/modules/force_password_change/admin.css?v=2.0.11', ENT_QUOTES, 'UTF-8'); ?>">
<main class="force-password-shell">
    <header class="force-password-head wbce-admin-header"><h2><?php echo htmlspecialchars(fpc_t('change_title'), ENT_QUOTES, 'UTF-8'); ?></h2><p><?php echo htmlspecialchars(fpc_t('change_subtitle'), ENT_QUOTES, 'UTF-8'); ?></p></header>
    <section class="force-password-card content-box wbce-admin-card">
        <p><?php echo htmlspecialchars(fpc_t('change_intro'), ENT_QUOTES, 'UTF-8'); ?></p>
        <div class="error wbce-admin-toast wbce-admin-toast--error" role="alert" data-password-error<?php echo $error === '' ? ' hidden' : ''; ?>><?php echo $error; ?></div>
        <form method="post" autocomplete="off" data-password-form>
            <?php echo $admin->getFTAN(); ?>
            <div class="force-password-grid">
                <label><?php echo htmlspecialchars(fpc_t('new_password'), ENT_QUOTES, 'UTF-8'); ?></label><div><?php echo $admin->passwordField('new_password'); ?></div>
                <label for="forced-password-confirmation"><?php echo htmlspecialchars(fpc_t('repeat_password'), ENT_QUOTES, 'UTF-8'); ?></label><input id="forced-password-confirmation" type="password" name="new_password_confirmation" required autocomplete="new-password">
            </div>
            <div class="force-password-actions"><button type="submit" class="button wbce-admin-button" data-password-submit><i class="fa fa-save" aria-hidden="true"></i> <span><?php echo htmlspecialchars(fpc_t('save_password'), ENT_QUOTES, 'UTF-8'); ?></span></button></div>
        </form>
    </section>
</main>
<script>
(function(){
    'use strict';
    var form=document.querySelector('[data-password-form]');
    if(!form||typeof window.fetch!=='function')return;
    var button=form.querySelector('[data-password-submit]'),label=button.querySelector('span'),errorBox=document.querySelector('[data-password-error]');
    form.addEventListener('submit',function(event){
        event.preventDefault();
        if(button.disabled)return;
        var original=label.textContent;
        button.disabled=true;button.setAttribute('aria-busy','true');label.textContent=<?php echo json_encode(fpc_t('saving_password')); ?>;errorBox.hidden=true;
        fetch(form.action||window.location.href,{method:'POST',body:new FormData(form),credentials:'same-origin',headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'}})
            .then(function(response){return response.text().then(function(raw){var data;try{data=JSON.parse(raw);}catch(ignore){throw new Error(<?php echo json_encode(fpc_t('invalid_response')); ?>+' (HTTP '+response.status+')');}if(data.ftan){var holder=document.createElement('div');holder.innerHTML=data.ftan;var fresh=holder.querySelector('input[type="hidden"]'),token=form.querySelector('input[type="hidden"]');if(fresh&&token){token.name=fresh.name;token.value=fresh.value;}}if(!response.ok||!data.ok)throw new Error(data.message||<?php echo json_encode(fpc_t('password_save_failed')); ?>);return data;});})
            .then(function(data){window.location.assign(data.redirect);})
            .catch(function(error){errorBox.textContent=error.message;errorBox.hidden=false;button.disabled=false;button.removeAttribute('aria-busy');label.textContent=original;});
    });
}());
</script>
<?php $admin->print_footer(); ?>
