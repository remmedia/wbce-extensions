<?php
if (!defined('WB_PATH')) exit('Cannot access this file directly');

require_once __DIR__.'/compat.php';
$table = TABLE_PREFIX.'mod_page_seo_tool';
$database->query("CREATE TABLE IF NOT EXISTS `{$table}` (`settings_json` text NOT NULL)");
if ((int)wb_seo_db_value($database, "SELECT COUNT(*) FROM `{$table}`") === 0) {
    $defaults = array(
        'iTitleCount' => array('use' => true, 'optimum' => 50, 'minimum' => 30),
        'iDescriptionCount' => array('use' => true, 'optimum' => 150, 'minimum' => 90),
        'keywordsConfig' => array('use' => true, 'wordReplace' => 'keywords'),
        'rewriteUrl' => array('use' => false, 'dbString' => ''),
        'bUseRemainingChars' => true,
    );
    $database->query("INSERT INTO `{$table}` (`settings_json`) VALUES ('".$database->escapeString(json_encode($defaults))."')");
}
$database->query("UPDATE `{TP}addons` SET `function`='tool', `version`='0.8.5' WHERE `type`='module' AND `directory`='wbSeoTool'");
