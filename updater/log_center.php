<?php
defined('WB_PATH') or die('No direct access');
return array(
    'type' => 'update',
    'label' => function_exists('log_center_text') ? log_center_text('UPDATE') : 'Update',
    'description' => function_exists('log_center_text') ? log_center_text('UPDATE_DESCRIPTION') : 'Versionierte Protokolle der CMS-Aktualisierungen.',
);
