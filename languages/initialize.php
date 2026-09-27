<?php
defined('WB_PATH') or die('No direct access');
require_once __DIR__ . '/Worker.php';
$languagesWorker = array(
    'label' => 'Übersetzungen schützen',
    'description' => 'Stellt gespeicherte Übersetzungen wieder her, wenn eine Aktualisierung den Originalwert zurückgeschrieben hat.',
    'callable' => array('WbceLanguagesWorker', 'restore'),
    'managed_task' => true,
    'default_name' => 'Übersetzungen schützen',
    'default_cron' => '17 * * * *',
    'default_configuration' => array(),
);
if (function_exists('wbce_add_filter')) {
    wbce_add_filter('worker.definitions', static function ($definitions) use ($languagesWorker) { $definitions = (array)$definitions; $definitions['languages.restore'] = $languagesWorker; return $definitions; });
} elseif (is_file(WB_PATH . '/modules/worker/Registry.php')) {
    require_once WB_PATH . '/modules/worker/Registry.php'; WbceWorkerRegistry::register('languages.restore', $languagesWorker);
}
