<?php
$module_directory = 'worker';
$module_uuid = '74d289c4-73f8-4dff-90cb-2aa1e22c8bb4';
require_once __DIR__ . '/Language.php';
$module_name = 'Worker';
$module_function = 'tool,initialize';
$module_version='1.10.44';
$module_platform = '1.7.0';
$module_author = 'Mathias Lange';
$module_license = 'GNU GPL2 or later';
$module_description = 'Cron-compatible scheduler and asynchronous worker runner for WBCE modules.';
$module_icon = 'fa fa-clock-o';
$module_requires_php = '8.2.0';
$module_name = worker_t('module_name');
$module_description = worker_t('module_description');
