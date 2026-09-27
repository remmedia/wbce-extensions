<?php
defined('WB_PATH') or die('No direct access');
if (!$admin->get_permission('admintools')) die('Forbidden');
require_once __DIR__.'/Language.php';
require_once __DIR__.'/Client.php';
require_once __DIR__.'/Registry.php';
require_once __DIR__.'/LogViewer.php';

$message = '';
$failed = false;
$verifiedThisRequest = null;
$isSettings = (string)($_GET['page'] ?? '') === 'settings';
$definitions = WbceLogCenterRegistry::all();
WbceLogCenterLogViewer::handle($admin, $message, $failed);
$runtimeProfilerFile = WB_PATH.'/framework/RuntimeProfiler.php';
$runtimeProfilerAvailable = is_file($runtimeProfilerFile);
$runtimeConfigReply = null;
if ($runtimeProfilerAvailable) require_once $runtimeProfilerFile;
if ($isSettings && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['log_center_action'] ?? '') === 'runtime_access_settings') {
    if (!$admin->checkFTAN()) { $message = log_center_text('SECURITY'); $failed = true; }
    elseif (!$runtimeProfilerAvailable) { $message = log_center_text('RUNTIME_UNAVAILABLE'); $failed = true; }
    else { try { $config=WbceRuntimeProfiler::config(); $config['access_threshold_ms']=max(50,min(60000,(int)($_POST['access_threshold_ms'] ?? 500))); $config['access_sample_percent']=max(0,min(100,(int)($_POST['access_sample_percent'] ?? 0))); WbceRuntimeProfiler::saveConfig($config); $message=log_center_text('SAVED'); } catch (Throwable $e) { $message=log_center_text('FAILED').' '.$e->getMessage(); $failed=true; } }
}
if ($isSettings && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['log_center_action'] ?? '') === 'runtime_toggle') {
    if (!$admin->checkFTAN()) { $message = log_center_text('SECURITY'); $failed = true; }
    elseif (!$runtimeProfilerAvailable) { $message = log_center_text('RUNTIME_UNAVAILABLE'); $failed = true; }
    else { try { $config = WbceRuntimeProfiler::config(); $name = (string)($_POST['runtime_name'] ?? ''); if (!in_array($name, array('enabled','trace','access','access_slow','access_errors','access_ajax_api_errors','access_background','access_sampling','admin_display','frontend_display'), true)) throw new InvalidArgumentException('Invalid runtime setting.'); $config[$name] = !empty($_POST['enabled']); if ($name === 'trace' && $config[$name]) $config['enabled'] = true; if (!$config['enabled']) { $config['trace'] = false; $config['access'] = false; $config['admin_display'] = false; $config['frontend_display'] = false; } WbceRuntimeProfiler::saveConfig($config); if (class_exists('Settings')) Settings::Set('log_center_installed', true); $runtimeConfigReply = WbceRuntimeProfiler::config(); $message = log_center_text('SAVED'); } catch (Throwable $e) { $message = log_center_text('FAILED').' '.$e->getMessage(); $failed = true; } }
}
if ($isSettings && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['log_center_action'] ?? '') === 'runtime_toggle' && strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest') {
    while (ob_get_level() > 0) ob_end_clean();
    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    echo json_encode(array('ok' => !$failed, 'message' => $message, 'config' => $runtimeConfigReply));
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['log_center_action'] ?? '') === 'connection' && $isSettings) {
    $reply=array('ok'=>false,'message'=>log_center_text('FAILED'));
    if(!$admin->checkFTAN())$reply['message']=log_center_text('SECURITY');
    else {$enabled=!empty($_POST['enabled']);$ok=true;$detail='';if($enabled){$domain=WbceLogCenterClient::normalizeDomainInput(Settings::GetDb('log_center_url',''));$token=trim((string)Settings::GetDb('log_center_token',''));list($ok,$detail,$endpoint)=WbceLogCenterClient::probe($domain,$token);Settings::Set('log_center_connection_ok',$ok);Settings::Set('log_center_connection_checked_at',time());if($ok)Settings::Set('log_center_endpoint',$endpoint);}$write=$ok?Settings::Set('log_center_connection_enabled',$enabled):'failed';$stored=filter_var(Settings::GetDb('log_center_connection_enabled',false),FILTER_VALIDATE_BOOLEAN);$reply['ok']=$ok&&$write===false&&$stored===$enabled;$reply['message']=$reply['ok']?log_center_text($enabled?'CONNECTION_ENABLED':'CONNECTION_DISABLED'):trim(log_center_text('UNREACHABLE').' '.$detail);}
    $reply['ftan']=$admin->getFTAN();while(ob_get_level()>0)ob_end_clean();header('Content-Type: application/json; charset=UTF-8');header('Cache-Control: no-store');echo json_encode($reply);exit;
}

/* Persist one service independently. This prevents unchecked controls from
 * overwriting the state of other registered services. */
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['log_center_action'] ?? '') === 'service' && $isSettings) {
    $reply = array('ok' => false, 'enabled' => false, 'message' => log_center_text('FAILED'));
    if (!$admin->checkFTAN()) {
        $reply['message'] = log_center_text('SECURITY');
    } else {
        $type = strtolower(trim((string)($_POST['service_type'] ?? '')));
        $enabled = !empty($_POST['enabled']);
        if (!isset($definitions[$type])) {
            $reply['message'] = log_center_text('UNKNOWN_SERVICE');
        } else {
            $writeError = Settings::Set('log_center_'.$type.'_enabled', $enabled);
            $stored = filter_var(Settings::GetDb('log_center_'.$type.'_enabled', false), FILTER_VALIDATE_BOOLEAN);
            $reply['enabled'] = $stored;
            $reply['ok'] = ($writeError === false && $stored === $enabled);
            $reply['message'] = $reply['ok'] ? log_center_text('SERVICE_SAVED') : log_center_text('FAILED');
        }
    }
    $reply['ftan'] = $admin->getFTAN();
    while (ob_get_level() > 0) ob_end_clean();
    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    echo json_encode($reply);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && (string)($_POST['log_center_action'] ?? '') === '' && $isSettings) {
    if (!$admin->checkFTAN()) {
        $message = log_center_text('SECURITY');
        $failed = true;
    } else {
        $url = WbceLogCenterClient::normalizeDomainInput($_POST['url'] ?? '');
        $tokenInput = trim((string)($_POST['token'] ?? ''));
        $token = $tokenInput !== '' ? $tokenInput : (string)Settings::GetDb('log_center_token', '');
        $states = array();
        foreach ($definitions as $type => $definition) $states[$type] = !empty($_POST['service'][$type]);
        $hasConnectionData = $url !== '' || $token !== '';

        if ($hasConnectionData && ($url === '' || $token === '')) {
            Settings::Set('log_center_connection_ok', false);
            $verifiedThisRequest = false;
            $message = log_center_text('REQUIRED');
            $failed = true;
        } else {
            $ok = true;
            $detail = '';
            $resolvedUrl = $url;
            if ($hasConnectionData) list($ok, $detail, $resolvedUrl) = WbceLogCenterClient::probe($url, $token);
            if (!$ok) {
                $verifiedThisRequest = false;
                Settings::Set('log_center_url', $url);
                Settings::Set('log_center_endpoint', '');
                if ($tokenInput !== '') Settings::Set('log_center_token', $tokenInput);
                Settings::Set('log_center_connection_ok', false);
                Settings::Set('log_center_connection_checked_at', time());
                foreach ($states as $type => $state) Settings::Set('log_center_'.$type.'_enabled', false);
                $message = log_center_text('UNREACHABLE').' '.$detail;
                $failed = true;
            } else {
                $verifiedThisRequest = $hasConnectionData;
                $errors = array(
                    Settings::Set('log_center_url', $url),
                    Settings::Set('log_center_endpoint', $resolvedUrl),
                    Settings::Set('log_center_token', $token),
                    Settings::Set('log_center_connection_enabled', true),
                    Settings::Set('log_center_connection_ok', $hasConnectionData),
                    Settings::Set('log_center_connection_checked_at', time()),
                );
                foreach ($states as $type => $state) $errors[] = Settings::Set('log_center_'.$type.'_enabled', $state);
                $failed = (bool)array_filter($errors, static fn($value) => $value !== false);
                $message = $failed ? log_center_text('FAILED') : log_center_text('SAVED');
            }
        }
    }
}

$url = WbceLogCenterClient::normalizeDomainInput(Settings::GetDb('log_center_url', ''));
$tokenConfigured = trim((string)Settings::GetDb('log_center_token', '')) !== '';
$connectionEnabled = filter_var(Settings::GetDb('log_center_connection_enabled', true), FILTER_VALIDATE_BOOLEAN);
$connectionOk = $connectionEnabled && $url !== '' && $tokenConfigured && filter_var(Settings::GetDb('log_center_connection_ok', false), FILTER_VALIDATE_BOOLEAN);
if ($verifiedThisRequest !== null) {$connectionEnabled=$verifiedThisRequest;$connectionOk = $connectionEnabled && $url !== '' && $tokenConfigured && $verifiedThisRequest;}
$connectionConfigured = $url !== '' || $tokenConfigured;
$states = array();
foreach ($definitions as $type => $definition) $states[$type] = filter_var(Settings::GetDb('log_center_'.$type.'_enabled', false), FILTER_VALIDATE_BOOLEAN);
ob_start();
?>
<section class="log-center" data-language="<?=htmlspecialchars(log_center_language(),ENT_QUOTES,'UTF-8')?>" data-processing="<?=htmlspecialchars(log_center_text('PROCESSING'))?>" data-active="<?=htmlspecialchars(log_center_text('ACTIVE'))?>" data-inactive="<?=htmlspecialchars(log_center_text('INACTIVE'))?>">
 <header class="log-center-hero wbce-admin-header"><div><h2><?=htmlspecialchars(log_center_text('NAME'))?></h2><p><?=htmlspecialchars(log_center_text('DESCRIPTION'))?></p></div></header>
 <nav class="log-center-nav log-center-menu-card" aria-label="<?=htmlspecialchars(log_center_text('NAME'))?>"><a class="log-center-menu-link<?=$isSettings?'':' active'?>" href="<?=htmlspecialchars(ADMIN_URL.'/admintools/tool.php?tool=log_center')?>"><?=htmlspecialchars(log_center_text('LOCAL_LOGS'))?></a><a class="log-center-menu-link<?=$isSettings?' active':''?>" href="<?=htmlspecialchars(ADMIN_URL.'/admintools/tool.php?tool=log_center&page=settings')?>"><?=htmlspecialchars(log_center_text('SETTINGS'))?></a></nav>
 <div class="log-center-toast wbce-admin-toast<?=$failed?' error wbce-admin-toast--error':''?>"<?=$message===''?' hidden':''?> role="<?=$failed?'alert':'status'?>"><?=htmlspecialchars($message)?></div>
 <?php if ($isSettings): ?>
 <form method="post" class="log-center-card wbce-admin-card" data-log-center-form>
  <?=$admin->getFTAN()?>
  <h3><?=htmlspecialchars(log_center_text('CONNECTION'))?></h3>
  <?php if ($connectionConfigured): ?><div class="log-center-connection-status <?=$connectionOk?'success':($connectionEnabled?'error':'disabled')?>" role="status"><span aria-hidden="true"></span><strong><?=htmlspecialchars(log_center_text($connectionOk?'CONNECTION_OK':($connectionEnabled?'CONNECTION_ERROR':'CONNECTION_DISABLED')))?></strong><?php if ($url !== ''): ?><small><?=htmlspecialchars($url)?></small><?php endif ?><label class="log-center-connection-switch"><input type="checkbox" data-log-center-connection<?=$connectionEnabled?' checked':''?>><i></i></label></div><?php endif ?>
  <div class="log-center-fields"<?=$connectionConfigured?' hidden':''?> data-log-center-connection-fields><label><?=htmlspecialchars(log_center_text('DOMAIN'))?><input type="text" name="url" value="<?=htmlspecialchars($url)?>" placeholder="example.org" inputmode="url" autocomplete="url"></label><label><?=htmlspecialchars(log_center_text('TOKEN'))?><input type="password" name="token" autocomplete="new-password" placeholder="<?=htmlspecialchars(log_center_text('TOKEN_KEEP'))?>"></label></div>
  <div class="log-center-connection-actions"><?php if ($connectionConfigured): ?><button class="button wbce-admin-button" type="button" data-log-center-edit><?=htmlspecialchars(log_center_text('EDIT_CONNECTION'))?></button><?php endif ?><button class="button wbce-admin-button" type="submit"<?=$connectionConfigured?' hidden':''?> data-log-center-save><?=htmlspecialchars(log_center_text('SAVE'))?></button></div>
  <?php if ($connectionOk): ?><div class="log-center-service-section"><h3><?=htmlspecialchars(log_center_text('SERVICES'))?></h3><div class="log-center-services"><?php foreach($definitions as $type=>$definition): ?><label class="log-center-service"><span><strong><?=htmlspecialchars($definition['label'])?></strong><small><?=htmlspecialchars($definition['description'])?></small><em data-log-center-state><?=htmlspecialchars(log_center_text($states[$type]?'ACTIVE':'INACTIVE'))?></em></span><input type="checkbox" data-log-center-service="<?=htmlspecialchars($type)?>"<?=$states[$type]?' checked':''?>><i></i></label><?php endforeach ?></div></div><?php endif ?>
 </form>
 <?php if ($runtimeProfilerAvailable): $runtimeConfig=WbceRuntimeProfiler::config(); ?>
 <section class="log-center-card log-center-runtime"><h3><?=htmlspecialchars(log_center_text('ADVANCED_OPTIONS'))?></h3><p><?=htmlspecialchars(log_center_text('ADVANCED_OPTIONS_HELP'))?></p>
  <div class="log-center-runtime-groups">
   <section class="log-center-runtime-group"><header><h4><?=htmlspecialchars(log_center_text('RUNTIME_GROUP_RECORDING'))?></h4><p><?=htmlspecialchars(log_center_text('RUNTIME_GROUP_RECORDING_HELP'))?></p></header><div class="log-center-runtime-options">
    <?php foreach (array('enabled'=>array('RUNTIME_LOG','RUNTIME_LOG_HELP',false),'trace'=>array('RUNTIME_TRACE','RUNTIME_TRACE_HELP',false),'access'=>array('ACCESS_LOG','ACCESS_LOG_HELP',true)) as $runtimeName=>$runtimeText): ?><form method="post" data-log-center-runtime-form><input type="hidden" name="log_center_action" value="runtime_toggle"><input type="hidden" name="runtime_name" value="<?=htmlspecialchars($runtimeName)?>"><?=$admin->getFTAN()?><label class="log-center-service"><span><strong><?=htmlspecialchars(log_center_text($runtimeText[0]))?></strong><small><?=htmlspecialchars(log_center_text($runtimeText[1]))?></small></span><input type="checkbox" name="enabled" value="1" data-log-center-runtime<?=$runtimeConfig[$runtimeName]?' checked':''?><?=$runtimeText[2]&&!$runtimeConfig['enabled']?' disabled':''?>><i></i></label></form><?php endforeach ?>
   </div></section>
   <section class="log-center-runtime-group" data-log-center-runtime-display-group<?=$runtimeConfig['enabled']&&$runtimeConfig['trace']?'':' hidden'?>><header><h4><?=htmlspecialchars(log_center_text('RUNTIME_GROUP_DISPLAY'))?></h4><p><?=htmlspecialchars(log_center_text('RUNTIME_GROUP_DISPLAY_HELP'))?></p></header><div class="log-center-runtime-options">
    <?php foreach (array('admin_display'=>array('ADMIN_RUNTIME_DISPLAY','ADMIN_RUNTIME_DISPLAY_HELP'),'frontend_display'=>array('FRONTEND_RUNTIME_DISPLAY','FRONTEND_RUNTIME_DISPLAY_HELP')) as $runtimeName=>$runtimeText): ?><form method="post" data-log-center-runtime-form><input type="hidden" name="log_center_action" value="runtime_toggle"><input type="hidden" name="runtime_name" value="<?=htmlspecialchars($runtimeName)?>"><?=$admin->getFTAN()?><label class="log-center-service"><span><strong><?=htmlspecialchars(log_center_text($runtimeText[0]))?></strong><small><?=htmlspecialchars(log_center_text($runtimeText[1]))?></small></span><input type="checkbox" name="enabled" value="1" data-log-center-runtime<?=$runtimeConfig[$runtimeName]?' checked':''?><?=$runtimeConfig['enabled']&&$runtimeConfig['trace']?'':' disabled'?>><i></i></label></form><?php endforeach ?>
   </div></section>
   <section class="log-center-runtime-group log-center-runtime-group--access"><header><h4><?=htmlspecialchars(log_center_text('RUNTIME_GROUP_ACCESS'))?></h4><p><?=htmlspecialchars(log_center_text('RUNTIME_GROUP_ACCESS_HELP'))?></p></header><div class="log-center-runtime-options">
    <?php foreach (array('access_slow'=>array('ACCESS_SLOW','ACCESS_SLOW_HELP'),'access_errors'=>array('ACCESS_ERRORS','ACCESS_ERRORS_HELP'),'access_ajax_api_errors'=>array('ACCESS_AJAX_API_ERRORS','ACCESS_AJAX_API_ERRORS_HELP'),'access_background'=>array('ACCESS_BACKGROUND','ACCESS_BACKGROUND_HELP'),'access_sampling'=>array('ACCESS_SAMPLING','ACCESS_SAMPLING_HELP')) as $runtimeName=>$runtimeText): ?><form method="post" data-log-center-runtime-form><input type="hidden" name="log_center_action" value="runtime_toggle"><input type="hidden" name="runtime_name" value="<?=htmlspecialchars($runtimeName)?>"><?=$admin->getFTAN()?><label class="log-center-service"><span><strong><?=htmlspecialchars(log_center_text($runtimeText[0]))?></strong><small><?=htmlspecialchars(log_center_text($runtimeText[1]))?></small></span><input type="checkbox" name="enabled" value="1" data-log-center-runtime<?=$runtimeConfig[$runtimeName]?' checked':''?><?=$runtimeConfig['access']?'':' disabled'?>><i></i></label></form><?php endforeach ?>
   </div><form method="post" class="log-center-access-options"><input type="hidden" name="log_center_action" value="runtime_access_settings"><?=$admin->getFTAN()?><label><?=htmlspecialchars(log_center_text('SLOW_THRESHOLD'))?><input type="number" name="access_threshold_ms" min="50" max="60000" step="50" value="<?=htmlspecialchars((string)$runtimeConfig['access_threshold_ms'])?>"><small><?=htmlspecialchars(log_center_text('MILLISECONDS'))?></small></label><label><?=htmlspecialchars(log_center_text('SUCCESS_SAMPLE'))?><input type="number" name="access_sample_percent" min="0" max="100" step="1" value="<?=htmlspecialchars((string)$runtimeConfig['access_sample_percent'])?>"><small><?=htmlspecialchars(log_center_text('PERCENT'))?></small></label><button class="button wbce-admin-button" type="submit"><?=htmlspecialchars(log_center_text('SAVE'))?></button></form></section>
  </div></section>
 <?php endif ?>
 <?php else: ?><?=WbceLogCenterLogViewer::render($admin)?><?php endif ?>
</section>
<link rel="stylesheet" href="<?=htmlspecialchars(WB_URL.'/modules/log_center/backend.css?v=1.1.48',ENT_QUOTES,'UTF-8')?>">
<script src="<?=htmlspecialchars(WB_URL.'/modules/log_center/backend.js?v=1.1.48',ENT_QUOTES,'UTF-8')?>" defer></script>
<?php
$content = ob_get_clean();
require_once __DIR__.'/TwigView.php';
WbceLogCenterTwigView::display($content);
