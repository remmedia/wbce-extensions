<?php
defined('WB_PATH') or die('No direct access');
require_once __DIR__.'/src/Config.php';
require_once __DIR__.'/Language.php';
if(!WbceAccessibilityToolsConfig::ensureSchema($database))throw new RuntimeException(accessibility_tools_texts()['database_unavailable']);
require_once __DIR__.'/src/OpfFallback.php';
WbceAccessibilityToolsOpfFallback::unregister();
$result=$database->query("UPDATE `{TP}addons` SET `function`='tool,preinit,initialize,snippet' WHERE `directory`='accessibility_tools' AND `type`='module'");
if(!is_object($result)||method_exists($result,'error')&&(string)$result->error()!=='')throw new RuntimeException(accessibility_tools_texts()['write_failed']);
// Existing activation and module settings deliberately remain untouched on update.
$obsolete=array(__DIR__.'/opf_filter.php');
foreach($obsolete as $file)if(is_file($file)&&is_writable($file))unlink($file);
