<?php
require_once __DIR__.'/Language.php';

// Primary CMS runtime: WBCE 1.7. The fallbacks in this file are the only
// compatibility path retained for WBCE 1.6.8 and can be removed together.
if (!defined('WBCE_STORE_PRIMARY_WBCE')) define('WBCE_STORE_PRIMARY_WBCE', '1.7.0');
if (!defined('WBCE_STORE_LEGACY_WBCE')) define('WBCE_STORE_LEGACY_WBCE', '1.6.8');
if (!defined('WBCE_STORE_PRIMARY_PHP')) define('WBCE_STORE_PRIMARY_PHP', '8.5');

if (!function_exists('wbce_store_db_value')) {
    function wbce_store_db_value($database, $sql)
    {
        if (method_exists($database, 'fetchValue')) return $database->fetchValue($sql);
        return $database->get_one($sql);
    }
}

if (!function_exists('wbce_store_native_hooks_available')) {
    function wbce_store_native_hooks_available()
    {
        foreach (array('wbce_add_action','wbce_add_filter','wbce_remove_hook','wbce_has_hook','wbce_do_action','wbce_apply_filters','wbce_apply_array_filters') as $function) {
            if (!function_exists($function)) return false;
            try {
                $file = (string)(new ReflectionFunction($function))->getFileName();
                if ($file === '' || strpos(str_replace('\\','/',$file), '/modules/wbce_hook_bridge/') !== false) return false;
            } catch (Throwable $ignored) { return false; }
        }
        return class_exists('WbceHookDispatcher') && class_exists('WbceCaptchaManager') && class_exists('WbceAuthFactorManager');
    }
}

if (!function_exists('wbce_store_normalize_cms_version')) {
    function wbce_store_normalize_cms_version($version)
    {
        return preg_match('/\d+(?:\.\d+){1,2}/', (string)$version, $match) ? $match[0] : '';
    }
}

if (!function_exists('wbce_store_cms_version_meets')) {
    function wbce_store_cms_version_meets($requiredVersion)
    {
        $required = wbce_store_normalize_cms_version($requiredVersion);
        $installed = defined('WBCE_VERSION') ? wbce_store_normalize_cms_version(WBCE_VERSION) : '';
        return $required === '' || ($installed !== '' && version_compare($installed, $required, '>='));
    }
}

if (!function_exists('wbce_store_any_requirement_meets')) {
    function wbce_store_any_requirement_meets($expression, $database = null)
    {
        $expression = trim((string)$expression);
        if ($expression === '') return true;
        foreach (preg_split('/\s*\|\s*/', $expression, -1, PREG_SPLIT_NO_EMPTY) as $requirement) {
            if (preg_match('/^WBCE\s*>=\s*([0-9][0-9A-Za-z._-]*)$/i', $requirement, $match)
                && wbce_store_cms_version_meets($match[1])) return true;
            if (preg_match('/^([a-z][a-z0-9_-]{1,189})\s*>=\s*([0-9][0-9A-Za-z._-]*)$/i', $requirement, $match)
                && $database) {
                if (strcasecmp($match[1], 'wbce_hook_bridge') === 0 && wbce_store_native_hooks_available()) return true;
                $slug = $database->escapeString($match[1]);
                $result = $database->query("SELECT `version` FROM `{TP}addons` WHERE `directory`='$slug' AND `type`='module' LIMIT 1");
                $row = $result ? $result->fetchRow(MYSQLI_ASSOC) : null;
                if ($row && version_compare((string)$row['version'], $match[2], '>=')) return true;
            }
        }
        return false;
    }
}

if (!function_exists('wbce_store_assert_captcha_provider_removable')) {
    function wbce_store_assert_captcha_provider_removable($database, $slug)
    {
        $providers = function_exists('wbce_captcha_providers') ? (array)wbce_captcha_providers() : array();
        $owned = array();
        $providerModules = array();
        foreach ($providers as $id => $definition) {
            if (!is_array($definition) || empty($definition['module'])) continue;
            $providerModules[(string)$definition['module']] = true;
            if ($definition['module'] === $slug) $owned[] = (string)$id;
        }
        $isConventionalProvider = strpos((string)$slug, 'captcha_') === 0 && $slug !== 'captcha_control';
        if (!$owned && !$isConventionalProvider) return;
        $selected = class_exists('Settings') ? (string)Settings::get('captcha_type', '') : '';
        if (in_array($selected, $owned, true) || $slug === 'captcha_' . $selected) {
            throw new RuntimeException(wbce_store_text('captcha_active_provider'));
        }

        // Hook registration only contains providers initialized for the current
        // request. Count the add-ons table as the source of truth so inactive or
        // later-initialized, but installed providers are not overlooked.
        $result = $database->query("SELECT `directory` FROM `{TP}addons` WHERE `type`='module' AND `directory` LIKE 'captcha\\_%'");
        while ($result && ($row = $result->fetchRow(MYSQLI_ASSOC))) {
            $directory = isset($row['directory']) ? (string)$row['directory'] : '';
            if ($directory !== '' && $directory !== 'captcha_control') $providerModules[$directory] = true;
        }
        unset($providerModules[(string)$slug]);
        if (count($providerModules) < 1) throw new RuntimeException(wbce_store_text('captcha_last_provider'));
    }
}

if (!function_exists('wbce_store_is_wbce17')) {
    function wbce_store_is_wbce17()
    {
        return wbce_store_cms_version_meets('1.7.0');
    }
}

if (!function_exists('wbce_store_render_twig')) {
    function wbce_store_render_twig($template, array $context, $fallbackHtml = '')
    {
        if (function_exists('getTwig')) {
            try {
                getTwig(__DIR__ . '/templates/')->load($template)->display($context);
                return;
            } catch (Throwable $ignored) { }
        }
        echo $fallbackHtml;
    }
}

if (!function_exists('wbce_store_has_native_addon_service')) {
    function wbce_store_has_native_addon_service()
    {
        return wbce_store_is_wbce17() && class_exists('AddonService');
    }
}

if (!function_exists('wbce_store_emit')) {
    function wbce_store_emit($event)
    {
        if (!function_exists('wbce_do_action')) return;
        $arguments = func_get_args();
        array_shift($arguments);
        call_user_func_array('wbce_do_action', array_merge(array($event), $arguments));
    }
}

if (!function_exists('wbce_store_remove_path')) {
    function wbce_store_remove_path($path)
    {
        if (function_exists('rm_full_dir') && is_dir($path)) return (bool) rm_full_dir($path);
        if (!file_exists($path)) return true;
        if (is_file($path) || is_link($path)) return @unlink($path);
        foreach ((array) scandir($path) as $item) {
            if ($item === '.' || $item === '..') continue;
            if (!wbce_store_remove_path($path . DIRECTORY_SEPARATOR . $item)) return false;
        }
        return @rmdir($path);
    }
}

if (!function_exists('wbce_store_ends_with')) {
    function wbce_store_ends_with($value, $suffix)
    {
        if (function_exists('str_ends_with')) return str_ends_with((string)$value, (string)$suffix);
        $suffix = (string)$suffix;
        return $suffix === '' || substr((string)$value, -strlen($suffix)) === $suffix;
    }
}

if (!function_exists('wbce_store_authorization_header')) {
    function wbce_store_authorization_header()
    {
        if (!empty($_SERVER['HTTP_AUTHORIZATION'])) return trim((string)$_SERVER['HTTP_AUTHORIZATION']);
        if (!empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) return trim((string)$_SERVER['REDIRECT_HTTP_AUTHORIZATION']);
        if (function_exists('getallheaders')) {
            foreach ((array)getallheaders() as $name => $value) {
                if (strcasecmp((string)$name, 'Authorization') === 0) return trim((string)$value);
            }
        }
        return '';
    }
}
