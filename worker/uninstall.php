<?php
if (!defined('WB_PATH')) { die('Access denied'); }
// Deliberately retain execution and trigger history. Some Store update paths
// perform an uninstall/install cycle; dropping these tables would erase the
// history during an otherwise normal module update.
$database->query('DROP TABLE IF EXISTS `{TP}mod_worker_tasks`');
