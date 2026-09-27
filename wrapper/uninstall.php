<?php
/**
 * Removes the Wrapper module table during module uninstallation.
 */

if (!defined('WB_PATH')) {
    exit('Cannot access this file directly');
}

$database->query('DROP TABLE IF EXISTS `'.TABLE_PREFIX.'mod_wrapper`');
