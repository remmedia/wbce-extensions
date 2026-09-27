<?php
defined('WB_PATH') or die('Direct access is not permitted.');
if (!function_exists('wbstats_db_value')) {
    function wbstats_db_value($database, string $sql)
    {
        return method_exists($database, 'fetchValue') ? $database->fetchValue($sql) : $database->get_one($sql);
    }
}
