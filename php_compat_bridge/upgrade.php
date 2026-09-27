<?php
if (!defined('WB_PATH')) { return; }
require_once __DIR__.'/preinit.php';
if (isset($database) && is_object($database) && is_callable(array($database, 'query'))) {
    $database->query("UPDATE `{TP}addons` SET `function`='preinit,initialize,snippet' WHERE `directory`='php_compat_bridge' AND `type`='module'");
}
