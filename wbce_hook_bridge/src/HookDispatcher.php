<?php

/**
 * Small, dependency-free hook dispatcher for WBCE 1.7 modules.
 *
 * Existing modules do not have to use it. New modules may register callbacks
 * from their initialize.php file.
 */
final class WbceHookDispatcher
{
    private static $hooks = array();
    private static $sequence = 0;
    private static $current = array();
    private static $runs = array();

    public static function add($name, $callback, $priority = 10)
    {
        if (!is_string($name) || $name === '' || !is_callable($callback)) {
            throw new InvalidArgumentException('Invalid WBCE hook registration');
        }
        $id = ++self::$sequence;
        self::$hooks[$name][$id] = array(
            'callback' => $callback,
            'priority' => (int)$priority,
            'sequence' => $id,
        );
        return array($name, $id);
    }

    public static function remove($handle)
    {
        if (!is_array($handle) || count($handle) !== 2) {
            return false;
        }
        list($name, $id) = $handle;
        if (!isset(self::$hooks[$name][$id])) {
            return false;
        }
        unset(self::$hooks[$name][$id]);
        return true;
    }

    public static function has($name)
    {
        return !empty(self::$hooks[$name]);
    }

    public static function removeAll($name = null)
    {
        if ($name === null) { self::$hooks = array(); return; }
        unset(self::$hooks[$name]);
    }

    public static function current()
    {
        return self::$current ? end(self::$current) : null;
    }

    public static function doing($name = null)
    {
        return $name === null ? !empty(self::$current) : in_array($name, self::$current, true);
    }

    public static function did($name)
    {
        return (int)(self::$runs[$name] ?? 0);
    }

    public static function action($name, array $arguments = array())
    {
        self::$runs[$name] = self::did($name) + 1;
        self::$current[] = $name;
        try {
            foreach (array_merge(self::callbacks('all'), self::callbacks($name)) as $entry) {
                $args = $entry['hook'] === 'all' ? array_merge(array($name), $arguments) : $arguments;
                call_user_func_array($entry['callback'], $args);
            }
        } finally { array_pop(self::$current); }
    }

    public static function filter($name, $value, array $arguments = array())
    {
        self::$runs[$name] = self::did($name) + 1;
        self::$current[] = $name;
        try {
            foreach (array_merge(self::callbacks('all'), self::callbacks($name)) as $entry) {
                if ($entry['hook'] === 'all') { call_user_func_array($entry['callback'], array_merge(array($name, $value), $arguments)); }
                else { $value = call_user_func_array($entry['callback'], array_merge(array($value), $arguments)); }
            }
            return $value;
        } finally { array_pop(self::$current); }
    }

    private static function callbacks($name)
    {
        $entries = isset(self::$hooks[$name]) ? self::$hooks[$name] : array();
        foreach ($entries as &$entry) $entry['hook'] = $name;
        unset($entry);
        uasort($entries, function ($left, $right) {
            if ($left['priority'] === $right['priority']) {
                return $left['sequence'] <=> $right['sequence'];
            }
            return $left['priority'] <=> $right['priority'];
        });
        return $entries;
    }
}

function wbce_add_action($name, $callback, $priority = 10)
{
    return WbceHookDispatcher::add($name, $callback, $priority);
}

function wbce_add_filter($name, $callback, $priority = 10)
{
    return WbceHookDispatcher::add($name, $callback, $priority);
}

function wbce_remove_hook($handle)
{
    return WbceHookDispatcher::remove($handle);
}

function wbce_has_hook($name)
{
    return WbceHookDispatcher::has($name);
}

function wbce_remove_all_hooks($name = null) { WbceHookDispatcher::removeAll($name); }
function wbce_current_hook() { return WbceHookDispatcher::current(); }
function wbce_doing_hook($name = null) { return WbceHookDispatcher::doing($name); }
function wbce_did_hook($name) { return WbceHookDispatcher::did($name); }

function wbce_do_action($name)
{
    $arguments = func_get_args();
    array_shift($arguments);
    WbceHookDispatcher::action($name, $arguments);
}

function wbce_apply_filters($name, $value)
{
    $arguments = func_get_args();
    array_shift($arguments);
    array_shift($arguments);
    return WbceHookDispatcher::filter($name, $value, $arguments);
}

/** Apply a filter whose contract requires an array result. */
function wbce_apply_array_filters($name, array $value)
{
    $arguments = func_get_args();
    array_shift($arguments);
    array_shift($arguments);
    $filtered = WbceHookDispatcher::filter($name, $value, $arguments);
    if (!is_array($filtered)) {
        throw new UnexpectedValueException('WBCE hook "' . $name . '" must return an array');
    }
    return $filtered;
}

/** A guard filter returns false to cancel the operation before persistent changes. */
function wbce_hook_allows($name, array $context = array())
{
    return WbceHookDispatcher::filter($name, true, array($context)) !== false;
}

/** Filter a redirect target without issuing the HTTP header itself. */
function wbce_redirect_url($url, $status = 302, array $context = array())
{
    $context['status'] = (int)$status;
    return (string)wbce_apply_filters('routing.redirect', (string)$url, $context);
}

/**
 * Return a redirect target only when it remains inside this WBCE installation.
 * Relative paths are resolved below WB_URL; protocol-relative and control
 * character based targets are rejected.
 */
function wbce_safe_redirect_url($url, $fallback = null)
{
    $fallback = $fallback === null ? WB_URL . '/' : (string)$fallback;
    $url = trim((string)$url);
    if ($url === '' || preg_match('/[\x00-\x1f\x7f]/', $url) || strpos($url, '//') === 0) {
        return $fallback;
    }

    if ($url[0] === '/') {
        $base = rtrim(WB_URL, '/');
        $basePath = (string)parse_url($base, PHP_URL_PATH);
        if ($basePath !== '' && strpos($url, rtrim($basePath, '/') . '/') !== 0 && $url !== $basePath) {
            return $fallback;
        }
        return (string)parse_url($base, PHP_URL_SCHEME) . '://' . (string)parse_url($base, PHP_URL_HOST)
            . (parse_url($base, PHP_URL_PORT) ? ':' . parse_url($base, PHP_URL_PORT) : '') . $url;
    }

    $target = parse_url($url);
    $base = parse_url(WB_URL);
    if ($target === false || $base === false || empty($target['scheme']) || empty($target['host'])) {
        return $fallback;
    }
    $targetPort = isset($target['port']) ? (int)$target['port'] : null;
    $basePort = isset($base['port']) ? (int)$base['port'] : null;
    if (strcasecmp($target['scheme'], $base['scheme']) !== 0
        || strcasecmp($target['host'], $base['host']) !== 0
        || $targetPort !== $basePort) {
        return $fallback;
    }
    $basePath = isset($base['path']) ? rtrim($base['path'], '/') : '';
    $targetPath = isset($target['path']) ? $target['path'] : '/';
    if ($basePath !== '' && $targetPath !== $basePath && strpos($targetPath, $basePath . '/') !== 0) {
        return $fallback;
    }
    return $url;
}
