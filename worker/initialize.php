<?php
if (!defined('WB_PATH')) { return; }
require_once __DIR__ . '/Registry.php';
require_once __DIR__ . '/Language.php';

$cleanupDefinition = array(
    'label' => worker_t('cleanup_label'),
    'description' => worker_t('cleanup_description'),
    'default_cron' => '0 3 * * *',
    'default_name' => worker_t('cleanup_label'),
    'default_configuration' => array('days' => 30),
    'managed_task' => true,
    'callable' => static function (array $configuration): array {
        global $database;
        $days = max(1, min(3650, (int)($configuration['days'] ?? 30)));
        $before = gmdate('Y-m-d H:i:s', time() - ($days * 86400));
        $database->query("DELETE FROM `{TP}mod_worker_logs` WHERE `started_at` < '" . $database->escapeString($before) . "'");
        return array('message' => worker_t('cleanup_done'));
    },
);
if (function_exists('wbce_add_filter')) {
wbce_add_filter('worker.definitions', static function (array $definitions) use ($cleanupDefinition): array {
    $definitions['worker.cleanup_logs'] = $cleanupDefinition;
    return $definitions;
});
} else {
    WbceWorkerRegistry::register('worker.cleanup_logs', $cleanupDefinition);
}

// Website-triggered mode applies to frontend and backend page requests. The
// internal endpoints opt out to prevent a trigger loop.
if (!defined('WBCE_WORKER_INTERNAL_REQUEST')) {
    require_once __DIR__.'/PageviewTrigger.php';
    WbceWorkerPageviewTrigger::register();
}
