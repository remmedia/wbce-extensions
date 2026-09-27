<?php
/** Removable WBCE 1.6.8 compatibility boundary for WBCE 1.7 modules. */
if (!function_exists('wbce_db_row')) {
    function wbce_db_row($result, bool $associative = true): ?array
    {
        if (!is_object($result)) return null;
        if (is_callable(array($result, 'fetchRow'))) {
            $row = $result->fetchRow($associative ? (defined('MYSQLI_ASSOC') ? MYSQLI_ASSOC : 1) : (defined('MYSQLI_NUM') ? MYSQLI_NUM : 2));
        } else {
            $method = $associative ? 'fetch_assoc' : 'fetch_row';
            $row = is_callable(array($result, $method)) ? $result->{$method}() : null;
        }
        return is_array($row) ? $row : null;
    }
}
if (!function_exists('wbce_db_value')) {
    function wbce_db_value($database, string $sql)
    {
        if (!is_object($database)) return null;
        if (is_callable(array($database, 'fetchValue'))) return $database->fetchValue($sql);
        if (is_callable(array($database, 'query'))) {
            $row = wbce_db_row($database->query($sql), false);
            if ($row) return reset($row);
        }
        return is_callable(array($database, 'get_one')) ? $database->get_one($sql) : null;
    }
}
if (!function_exists('wbce_db_execute')) {
    function wbce_db_execute($database, string $sql): bool
    {
        if (!is_object($database) || !is_callable(array($database, 'query'))) return false;
        try {
            $result = $database->query($sql);
            if ($result === false || $result === null) return false;
            if (is_callable(array($database, 'hasError'))) return !$database->hasError();
            if (is_callable(array($database, 'is_error'))) return !$database->is_error();
            return true;
        } catch (Throwable $error) { return false; }
    }
}
if (!function_exists('wbce_db_escape')) {
    function wbce_db_escape($database, $value): string
    {
        return is_object($database) && is_callable(array($database, 'escapeString')) ? $database->escapeString((string)$value) : addslashes((string)$value);
    }
}
if (!function_exists('h')) {
    function h($value): string { return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
}
if (!function_exists('wbce_safe_redirect_url')) {
    function wbce_safe_redirect_url($url, $fallback = '/')
    {
        if (!is_string($url) || $url === '' || preg_match('/[\x00-\x1F\x7F]/', $url)) return $fallback;
        if (!defined('WB_URL')) return $fallback;
        $target = parse_url($url); $base = parse_url(WB_URL);
        if (!is_array($target) || !is_array($base)) return $fallback;
        if (isset($target['scheme']) && strcasecmp((string)$target['scheme'], (string)($base['scheme'] ?? '')) !== 0) return $fallback;
        if (isset($target['host']) && strcasecmp((string)$target['host'], (string)($base['host'] ?? '')) !== 0) return $fallback;
        $path = (string)($target['path'] ?? '/');
        if (!isset($target['host']) && ($path === '' || $path[0] !== '/')) return $fallback;
        $basePath = rtrim((string)($base['path'] ?? ''), '/');
        if ($basePath !== '' && $path !== $basePath && strpos($path, $basePath.'/') !== 0) return $fallback;
        return $url;
    }
}
