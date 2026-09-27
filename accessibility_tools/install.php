<?php
defined('WB_PATH') or die('No direct access');
require_once __DIR__.'/src/Config.php';
require_once __DIR__.'/Language.php';
if(!WbceAccessibilityToolsConfig::ensureSchema($database))throw new RuntimeException(accessibility_tools_texts()['database_unavailable']);
$result=$database->query("UPDATE `{TP}addons` SET `function`='tool,preinit,initialize,snippet' WHERE `directory`='accessibility_tools' AND `type`='module'");
if(!is_object($result)||method_exists($result,'error')&&(string)$result->error()!=='')throw new RuntimeException(accessibility_tools_texts()['write_failed']);
if(method_exists($database,'field_exists')&&$database->field_exists('{TP}addons','active')){$result=$database->query("UPDATE `{TP}addons` SET `active`=1 WHERE `directory`='accessibility_tools' AND `type`='module'");if(!is_object($result)||method_exists($result,'error')&&(string)$result->error()!=='')throw new RuntimeException(accessibility_tools_texts()['write_failed']);}
