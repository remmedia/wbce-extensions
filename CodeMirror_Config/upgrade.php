<?php
defined('WB_PATH') or die("This file can't be accessed directly!");

// The settings installer is idempotent and only supplies missing defaults.
require __DIR__.'/install.php';
if (isset($database) && is_object($database) && is_callable(array($database, 'query'))) {
    $database->query("UPDATE `{TP}addons` SET `function`='tool, initialize', `version`='0.1.10' WHERE `type`='module' AND `directory`='CodeMirror_Config'");
}
