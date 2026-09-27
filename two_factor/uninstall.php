<?php
defined('WB_PATH') or die('No direct access');
require_once __DIR__.'/Language.php';
if(!is_object($database)||!is_callable(array($database,'query'))){
    throw new RuntimeException(wbce_two_factor_t('database_unavailable'));
}
$database->query('DROP TABLE IF EXISTS `{TP}mod_two_factor_settings`');
$database->query('DROP TABLE IF EXISTS `{TP}mod_two_factor_users`');
