<?php
if (!defined('WB_PATH')) { return; }
if (isset($database) && is_object($database) && is_callable(array($database,'query'))) { $database->query("UPDATE `{TP}addons` SET `function`='preinit,initialize,snippet' WHERE `directory`='wbce_hook_bridge' AND `type`='module'"); }
