<?php

if (!function_exists('worker_db_value')) {
    /**
     * Read one database value with the WBCE 1.7 API and a self-contained
     * WBCE 1.6.8 fallback. Keeping this helper outside Language.php makes it
     * available during install and update before language handling starts.
     */
    function worker_db_value($database, $sql)
    {
        if (!is_object($database)) { return null; }
        if (method_exists($database, 'fetchValue')) {
            return $database->fetchValue($sql);
        }
        if (method_exists($database, 'query')) {
            $row = worker_db_row($database->query($sql), false);
            if (is_array($row) && $row !== array()) { return reset($row); }
        }
        // Last-resort fallback for unusual WBCE 1.6.8 database adapters that
        // expose no readable result object. Newer WBCE versions never reach it.
        if (method_exists($database, 'get_one')) {
            return $database->get_one($sql);
        }
        return null;
    }
}

if (!function_exists('worker_db_row')) {
    /** Read one row from WBCE 1.6.8, WBCE 1.7 or native mysqli results. */
    function worker_db_row($result, $associative = true)
    {
        if (!is_object($result)) { return null; }
        if (method_exists($result, 'fetchRow')) {
            $mode = $associative
                ? (defined('MYSQLI_ASSOC') ? MYSQLI_ASSOC : 1)
                : (defined('MYSQLI_NUM') ? MYSQLI_NUM : 2);
            $row = $result->fetchRow($mode);
            return is_array($row) ? $row : null;
        }
        $method = $associative ? 'fetch_assoc' : 'fetch_row';
        if (method_exists($result, $method)) {
            $row = $result->{$method}();
            return is_array($row) ? $row : null;
        }
        return null;
    }
}
