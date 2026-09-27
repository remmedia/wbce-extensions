<?php
defined('WB_PATH') or die('Cannot access this file directly');
if (!function_exists('menu_link_db_value')) {
    function menu_link_db_value($database, string $sql)
    {
        return is_object($database) && method_exists($database, 'fetchValue')
            ? $database->fetchValue($sql)
            : $database->get_one($sql);
    }
}
