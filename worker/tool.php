<?php
if (!defined('WB_PATH') || !isset($admin)) { die('Access denied'); }
require_once __DIR__ . '/Service.php';
require_once __DIR__ . '/Settings.php';
require_once __DIR__ . '/Language.php';
WbceWorkerSettings::install($database);
$workerSettingsNotice = '';
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['worker_settings_action']) && is_scalar($_POST['worker_settings_action'])) {
    if (!$admin->checkFTAN()) { $workerSettingsNotice = worker_t('settings_security'); }
    else {
        try {
            if ($_POST['worker_settings_action'] === 'save') {
                WbceWorkerSettings::setMode($database, isset($_POST['trigger_mode']) ? (string)$_POST['trigger_mode'] : 'cron');
                foreach (array('cron','url','pageview') as $intervalSource) {
                    if (isset($_POST[$intervalSource.'_interval'])) { WbceWorkerSettings::setInterval($database, $intervalSource, (int)$_POST[$intervalSource.'_interval']); }
                }
                WbceWorkerSettings::saveLimits($database, $_POST);
                $workerSettingsNotice = worker_t('settings_saved');
            } elseif ($_POST['worker_settings_action'] === 'renew_token') {
                WbceWorkerSettings::renewToken($database);
                $workerSettingsNotice = worker_t('token_renewed');
            }
        } catch (Throwable $exception) { $workerSettingsNotice = $exception->getMessage(); }
    }
}
$adminTriggerMode = WbceWorkerSettings::mode($database);
if ($adminTriggerMode === 'pageview') {
    require_once __DIR__.'/PageviewTrigger.php';
    WbceWorkerPageviewTrigger::register();
}
$definitions = WbceWorkerRegistry::all();
if (isset($definitions['worker.cleanup_logs'])) {
    $definitions['worker.cleanup_logs']['label'] = worker_t('cleanup_label');
    $definitions['worker.cleanup_logs']['description'] = worker_t('cleanup_description');
}
$publicDefinitions = array();
foreach ($definitions as $definitionId => $definition) {
    $publicDefinitions[$definitionId] = array('label'=>$definition['label'], 'description'=>$definition['description'], 'default_cron'=>$definition['default_cron'], 'show_tasks'=>$definition['show_tasks'], 'show_process'=>$definition['show_process'], 'managed_task'=>$definition['managed_task']);
}
$triggerMode = WbceWorkerSettings::mode($database);
$triggerInterval = WbceWorkerSettings::interval($database, $triggerMode);
$workerLimits = WbceWorkerSettings::limits($database);
$cronMinute = $triggerInterval === 1 ? '*' : '*/'.$triggerInterval;
$cronCommand = $cronMinute.' * * * * nice -n 10 ' . (defined('PHP_BINARY') && PHP_BINARY ? PHP_BINARY : '/usr/bin/php') . ' ' . __DIR__ . '/cron.php >/dev/null 2>&1';
$urlToken = WbceWorkerSettings::get($database, 'url_token', '');
$urlCron = WB_URL.'/modules/worker/webcron.php?token='.rawurlencode($urlToken);
$lastTrigger = WbceWorkerSettings::lastTrigger($database, $triggerMode);
$lastTriggerTimestamp = $lastTrigger !== '' ? strtotime($lastTrigger.' UTC') : false;
$workerDisplayTimezone = new DateTimeZone(date_default_timezone_get());
if (function_exists('wbce_timezone')) {
    try { $candidate = wbce_timezone(); if ($candidate instanceof DateTimeZone) $workerDisplayTimezone = $candidate; } catch (Throwable $ignored) { }
} elseif (defined('TIMEZONE_IDENTIFIER')) {
    try { $workerDisplayTimezone = new DateTimeZone((string) TIMEZONE_IDENTIFIER); } catch (Throwable $ignored) { }
}
$triggerOverdueAfter = ($triggerInterval * 60) + 10;
$triggerOverdue = $lastTriggerTimestamp === false || time() - $lastTriggerTimestamp > $triggerOverdueAfter;
$ftan = $admin->getFTAN();
$jsTexts = array();
foreach (array('all','request_failed','running','ready','successful','failed','skipped','never','registered','task_singular','task_plural','worker_missing','managed','toggle','next_run','run_now','edit','delete','registered_workers','no_workers','scheduled_tasks','no_tasks','last_runs','task','start','status','message','duration','no_runs','settings_save_error','settings_load_error','settings_updated','edit_task','new_task','copied','launched','delete_confirm','task_deleted','status_saved','token_confirm','task_saved','heartbeat_success','heartbeat_skipped','cleanup_done','task_completed','active_schedules','no_active_schedule','processing','cron_diagnostics','source','finished','last_phase','started_tasks') as $jsTextKey) { $jsTexts[$jsTextKey] = worker_t($jsTextKey); }
require_once __DIR__.'/TwigView.php';
ob_start();
?>
<link rel="stylesheet" href="<?php echo WB_URL; ?>/modules/worker/backend.css?v=1.10.42">
<section class="worker-app" data-endpoint="<?php echo WB_URL; ?>/modules/worker/ajax.php">
  <header class="worker-hero wbce-admin-header">
    <div><span class="worker-kicker"><?php echo htmlspecialchars(worker_t('hero_kicker')); ?></span><h2><?php echo htmlspecialchars(worker_t('module_name')); ?></h2><p><?php echo htmlspecialchars(worker_t('hero_text')); ?></p></div>
    <button class="worker-primary button wbce-admin-button" type="button" data-new><?php echo htmlspecialchars(worker_t('new_task')); ?></button>
  </header>
  <div class="worker-notice wbce-admin-toast" data-notice hidden></div>
  <?php if ($workerSettingsNotice !== ''): ?><div class="worker-settings-notice"><?php echo htmlspecialchars($workerSettingsNotice); ?></div><?php endif; ?>
  <div class="worker-heartbeat <?php echo $triggerOverdue ? 'is-warning' : 'is-ok'; ?>">
    <strong><?php echo htmlspecialchars(worker_t('last_call')); ?></strong>
    <span><?php echo $lastTriggerTimestamp === false ? htmlspecialchars(worker_t('never_called')) : htmlspecialchars((new DateTimeImmutable('@'.$lastTriggerTimestamp))->setTimezone($workerDisplayTimezone)->format('d.m.Y H:i:s')); ?></span>
    <?php $unit = worker_t($triggerInterval === 1 ? 'minute' : 'minutes'); if ($triggerOverdue): ?><small><?php echo htmlspecialchars(worker_t('warning', array('{minutes}'=>(string)$triggerInterval,'{unit}'=>$unit))); ?></small><?php else: ?><small><?php echo htmlspecialchars(worker_t('heartbeat_ok')); ?></small><?php endif; ?>
  </div>
  <section class="worker-trigger-settings content-box wbce-admin-card">
    <h3><?php echo htmlspecialchars(worker_t('execution')); ?></h3>
    <form method="post" data-worker-settings="mode">
      <?php echo $admin->getFTAN(); ?><input type="hidden" name="worker_settings_action" value="save">
      <div class="worker-trigger-options">
        <label><input type="radio" name="trigger_mode" value="cron" <?php echo $triggerMode === 'cron' ? 'checked' : ''; ?>><span><strong><?php echo htmlspecialchars(worker_t('mode_cron')); ?></strong><small><?php echo htmlspecialchars(worker_t('mode_cron_help')); ?></small></span></label>
        <label><input type="radio" name="trigger_mode" value="url" <?php echo $triggerMode === 'url' ? 'checked' : ''; ?>><span><strong><?php echo htmlspecialchars(worker_t('mode_url')); ?></strong><small><?php echo htmlspecialchars(worker_t('mode_url_help')); ?></small></span></label>
        <label><input type="radio" name="trigger_mode" value="pageview" <?php echo $triggerMode === 'pageview' ? 'checked' : ''; ?>><span><strong><?php echo htmlspecialchars(worker_t('mode_pageview')); ?></strong><small><?php echo htmlspecialchars(worker_t('mode_pageview_help')); ?></small></span></label>
      </div>
      <label class="worker-pageview-interval"><?php echo htmlspecialchars(worker_t('interval')); ?>
        <select name="<?php echo htmlspecialchars($triggerMode); ?>_interval">
          <?php foreach (WbceWorkerSettings::intervalOptions($triggerMode) as $intervalOption): ?><option value="<?php echo (int)$intervalOption; ?>" <?php echo $triggerInterval === $intervalOption ? 'selected' : ''; ?>><?php echo (int)$intervalOption.' '.htmlspecialchars(worker_t($intervalOption === 1 ? 'minute' : 'minutes')); ?></option><?php endforeach; ?>
        </select>
      </label>
      <div class="worker-runtime-limits">
        <label><?php echo htmlspecialchars(worker_t('max_tasks_per_run')); ?><input type="number" name="max_tasks_per_run" min="1" max="20" value="<?php echo (int)$workerLimits['max_tasks']; ?>"></label>
        <label><?php echo htmlspecialchars(worker_t('run_budget_seconds')); ?><input type="number" name="run_budget_seconds" min="5" max="300" value="<?php echo (int)$workerLimits['budget_seconds']; ?>"></label>
        <label><?php echo htmlspecialchars(worker_t('pause_between_tasks_ms')); ?><input type="number" name="pause_between_tasks_ms" min="0" max="5000" step="50" value="<?php echo (int)$workerLimits['pause_ms']; ?>"></label>
      </div>
      <small class="worker-runtime-limits-help"><?php echo htmlspecialchars(worker_t('runtime_limits_help')); ?></small>
      <button class="worker-primary worker-save-settings button wbce-admin-button" type="submit"><?php echo htmlspecialchars(worker_t('save_selection')); ?></button>
    </form>
    <?php if ($triggerMode === 'cron'): ?>
      <article class="worker-cron"><strong><?php echo htmlspecialchars(worker_t('system_cron', array('{minutes}'=>(string)$triggerInterval, '{unit}'=>$unit))); ?></strong><code><?php echo htmlspecialchars($cronCommand); ?></code><button type="button" data-copy><?php echo htmlspecialchars(worker_t('copy')); ?></button></article>
    <?php elseif ($triggerMode === 'url'): ?>
      <article class="worker-cron"><strong><?php echo htmlspecialchars(worker_t('url_cron', array('{minutes}'=>(string)$triggerInterval, '{unit}'=>$unit))); ?></strong><code><?php echo htmlspecialchars($urlCron); ?></code><button type="button" data-copy><?php echo htmlspecialchars(worker_t('copy')); ?></button></article>
      <form method="post" class="worker-renew-token" data-worker-settings="token"><?php echo $admin->getFTAN(); ?><input type="hidden" name="worker_settings_action" value="renew_token"><button type="submit"><?php echo htmlspecialchars(worker_t('renew_token')); ?></button></form>
    <?php else: ?>
      <p class="worker-pageview-hint"><?php echo htmlspecialchars(worker_t('pageview_hint', array('{minutes}'=>(string)$triggerInterval, '{unit}'=>$unit))); ?></p>
    <?php endif; ?>
  </section>
  <div data-content><div class="worker-loading"><?php echo htmlspecialchars(worker_t('loading')); ?></div></div>
</section>
<dialog class="worker-dialog wbce-admin-dialog" data-dialog>
  <form method="dialog" data-form>
    <div class="worker-dialog-head"><div><span class="worker-kicker"><?php echo htmlspecialchars(worker_t('task')); ?></span><h3 data-dialog-title><?php echo htmlspecialchars(worker_t('new_task')); ?></h3></div><button value="cancel" aria-label="<?php echo htmlspecialchars(worker_t('close')); ?>">×</button></div>
    <?php echo $ftan; ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="0">
    <label><?php echo htmlspecialchars(worker_t('name')); ?><input name="name" required maxlength="150"></label>
    <label><?php echo htmlspecialchars(worker_t('worker')); ?><select name="worker_id" required><?php foreach ($definitions as $id => $definition): if (!$definition['show_tasks'] || $definition['managed_task']) { continue; } ?><option value="<?php echo htmlspecialchars($id); ?>"><?php echo htmlspecialchars($definition['label']); ?></option><?php endforeach; ?></select></label>
    <label><?php echo htmlspecialchars(worker_t('cron_schema')); ?><input name="cron_expression" required value="0 * * * *" placeholder="*/5 * * * *"><small><?php echo htmlspecialchars(worker_t('cron_help')); ?></small></label>
    <label><?php echo htmlspecialchars(worker_t('configuration')); ?><textarea name="configuration" rows="5">{}</textarea></label>
    <label class="worker-check"><input type="checkbox" name="active" value="1" checked> <?php echo htmlspecialchars(worker_t('activate_task')); ?></label>
    <footer><button value="cancel" type="button" data-cancel><?php echo htmlspecialchars(worker_t('cancel')); ?></button><button class="worker-primary" value="default" type="submit"><?php echo htmlspecialchars(worker_t('save')); ?></button></footer>
  </form>
</dialog>
<dialog class="worker-dialog wbce-admin-dialog" data-confirm-dialog><form method="dialog"><p data-confirm-message></p><footer><button class="button wbce-admin-button" value="cancel"><?php echo htmlspecialchars(worker_t('cancel')); ?></button><button class="button wbce-admin-button" value="confirm"><?php echo htmlspecialchars(worker_t('confirm')); ?></button></footer></form></dialog>
<script>window.WBCE_WORKER_DEFINITIONS=<?php echo json_encode($publicDefinitions, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;window.WBCE_WORKER_TEXTS=<?php echo json_encode($jsTexts, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;</script>
<script src="<?php echo WB_URL; ?>/modules/worker/backend.js?v=1.10.42"></script>
<?php
$workerLegacyContent = ob_get_clean();
WbceCompatibleTwigView::display(__DIR__.'/templates', 'tool.twig', array('content'=>$workerLegacyContent,'module_version'=>$module_version), $workerLegacyContent);
