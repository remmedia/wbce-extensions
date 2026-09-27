<?php

defined('WB_PATH') or exit;

function toolAccountSettingsEnsureSchema($database)
{
    if (!is_object($database) || !method_exists($database, 'field_exists') || !method_exists($database, 'field_add')) {
        return false;
    }

    $columns = array(
        'signup_confirmcode' => "VARCHAR(64) DEFAULT ''",
        'signup_checksum' => "VARCHAR(64) DEFAULT ''",
        'signup_timeout' => "INT(11) NOT NULL DEFAULT '0'",
        'signup_timestamp' => "INT(11) NOT NULL DEFAULT '0'",
        'gdpr_check' => "INT(1) NOT NULL DEFAULT '0'",
    );
    foreach ($columns as $name => $definition) {
        if (!$database->field_exists('{TP}users', $name) && !$database->field_add('{TP}users', $name, $definition)) {
            return false;
        }
    }
    return true;
}
