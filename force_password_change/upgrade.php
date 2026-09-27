<?php
defined('WB_PATH') or die('No direct access');
global $database;
require_once __DIR__ . '/Service.php';

if (!WbceForcePasswordChangeService::installSchema($database)) {
    throw new RuntimeException(fpc_t('schema_upgrade_failed'));
}
