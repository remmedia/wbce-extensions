<?php
/** Removable WBCE 1.6.8 compatibility helpers. WBCE 1.7 remains the primary path. */
if (!function_exists('h')) {
    function h($value) { return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
}
if (!function_exists('L_')) {
    function L_($key)
    {
        $parts = explode('||', (string)$key, 2);
        $token = trim($parts[0], '{}');
        if (strpos($token, ':') !== false) {
            list($group, $name) = explode(':', $token, 2);
            if (isset($GLOBALS[$group]) && is_array($GLOBALS[$group]) && isset($GLOBALS[$group][$name])) return $GLOBALS[$group][$name];
        }
        return isset($parts[1]) ? $parts[1] : $token;
    }
}
if (!function_exists('sanitizeCssColor')) {
    function sanitizeCssColor(?string $color): string
    {
        $color = trim((string)$color);
        return $color === '' || preg_match('/^#[0-9a-f]{6}$/i', $color) ? $color : '';
    }
}
if (!function_exists('wbce_captcha_setting_exists')) {
    function wbce_captcha_setting_exists($name)
    {
        try {
            $suffix = bin2hex(random_bytes(4));
        } catch (Throwable $error) {
            $suffix = str_replace('.', '', uniqid('', true));
        }
        $missing = '__wbce_captcha_missing__' . $suffix;
        return Settings::get($name, $missing) !== $missing;
    }
}
if (!function_exists('wbce_captcha_setting_delete')) {
    function wbce_captcha_setting_delete($name)
    {
        global $database;
        if (!is_object($database) || !is_callable([$database, 'query'])) {
            return false;
        }
        $normalized = strtolower((string)$name);
        $escaped = is_callable([$database, 'escapeString'])
            ? $database->escapeString($normalized)
            : addslashes($normalized);
        $result = $database->query("DELETE FROM `{TP}settings` WHERE `name`='" . $escaped . "'");
        if (class_exists('Settings') && property_exists('Settings', 'aSettings')) {
            unset(Settings::$aSettings[$normalized]);
        }
        if ($result === false || $result === null) return false;
        if (is_object($result) && is_callable([$result, 'error'])) {
            $error = $result->error();
            if (is_string($error) && $error !== '') return false;
        }
        return true;
    }
}
