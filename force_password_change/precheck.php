<?php
defined('WB_PATH') or die('No direct access');
$native = (function_exists('wbce_add_action') && function_exists('wbce_add_filter'))
    || (defined('WBCE_VERSION') && version_compare((string) WBCE_VERSION, '1.7.0', '>='));
$result = !$native && isset($database) && is_object($database) && is_callable(array($database, 'query'))
    ? $database->query("SELECT `version` FROM `{TP}addons` WHERE `type`='module' AND `directory`='wbce_hook_bridge' LIMIT 1")
    : null;
$row = is_object($result) && is_callable(array($result, 'fetchRow')) ? $result->fetchRow(defined('MYSQLI_ASSOC') ? MYSQLI_ASSOC : 1)
    : (is_object($result) && is_callable(array($result, 'fetch_assoc')) ? $result->fetch_assoc() : null);
$hooks = $native || ($row && version_compare((string) $row['version'], '1.2.0', '>='));
$PRECHECK = array(
    'WBCE_VERSION' => array('VERSION' => '1.6.8', 'OPERATOR' => '>='),
    'PHP_VERSION' => array('VERSION' => '8.2.0', 'OPERATOR' => '>='),
    'CUSTOM_CHECKS' => array(
        'Hook-Schnittstellen' => array(
            'REQUIRED' => 'WBCE 1.7.0 oder Hook-Bridge 1.2.0',
            'ACTUAL' => $hooks ? 'Verfügbar' : 'Nicht verfügbar',
            'STATUS' => $hooks,
        ),
    ),
);
