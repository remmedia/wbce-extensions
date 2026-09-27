<?php
// Existing installations used disabled defaults although all three local
// producers already wrote events.  Enable the configured core transports.
// Administrators may still switch an individual service off afterwards.
defined('WB_PATH') or die('No direct access');
require __DIR__.'/install.php';
if (class_exists('Settings')) {
    foreach (array('error', 'backup', 'worker', 'runtime', 'update') as $type) {
        Settings::Set('log_center_'.$type.'_enabled', true);
    }
}

// Log rotation is owned by Log Center. Remove the legacy standalone
// Log Rotate task so it cannot remain in the scheduler after migration.
if (isset($database) && is_object($database) && method_exists($database, 'query')) {
    $hasTasks = !method_exists($database, 'table_exists') || $database->table_exists(TABLE_PREFIX . 'mod_worker_tasks');
    if ($hasTasks) {
        $database->query("DELETE FROM `{TP}mod_worker_tasks` WHERE `worker_id`='logrotate.rotate'");
    }
}
