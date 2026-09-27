<?php

/** Removable WBCE 1.6.8 compatibility layer. */
final class WbceForcePasswordChangeCompatibility
{
    public static function value($database, $sql)
    {
        if (!is_object($database)) return null;
        try {
            if (is_callable(array($database, 'fetchValue'))) return $database->fetchValue($sql);
            return is_callable(array($database, 'get_one')) ? $database->get_one($sql) : null;
        } catch (Throwable $error) {
            return null;
        }
    }

    public static function execute($database, $sql)
    {
        if (!is_object($database) || !is_callable(array($database, 'query'))) return false;
        try {
            $result = $database->query($sql);
            if ($result === false || $result === null) return false;
            if (is_callable(array($database, 'hasError'))) return !$database->hasError();
            if (is_callable(array($database, 'is_error'))) return !$database->is_error();
            if (is_object($result) && is_callable(array($result, 'error'))) {
                $message = $result->error();
                if (is_string($message) && $message !== '') return false;
            }
            return true;
        } catch (Throwable $error) {
            return false;
        }
    }

    public static function escape($database, $value)
    {
        return is_object($database) && is_callable(array($database, 'escapeString')) ? $database->escapeString((string) $value) : addslashes((string) $value);
    }

    public static function currentUserId()
    {
        return isset($_SESSION['USER_ID']) ? (int) $_SESSION['USER_ID'] : 0;
    }

    public static function safeRedirect($url, $fallback)
    {
        if (function_exists('wbce_safe_redirect_url')) return wbce_safe_redirect_url($url, $fallback);
        if (!is_string($url) || !defined('WB_URL')) return $fallback;
        if ($url === '' || preg_match('/[\x00-\x1F\x7F]/', $url)) return $fallback;
        $target = parse_url($url);
        $base = parse_url(WB_URL);
        if (!is_array($target) || !is_array($base)) return $fallback;
        if (isset($target['scheme']) && (!isset($base['scheme']) || strcasecmp($target['scheme'], $base['scheme']) !== 0)) return $fallback;
        if (isset($target['host']) && (!isset($base['host']) || strcasecmp($target['host'], $base['host']) !== 0)) return $fallback;
        if (isset($target['port']) && (int) $target['port'] !== (int) (isset($base['port']) ? $base['port'] : 0)) return $fallback;
        $basePath = isset($base['path']) ? rtrim($base['path'], '/') : '';
        $targetPath = isset($target['path']) ? $target['path'] : '/';
        if (!isset($target['scheme']) && !isset($target['host']) && (!isset($targetPath[0]) || $targetPath[0] !== '/')) return $fallback;
        if ($basePath !== '' && $targetPath !== $basePath && strpos($targetPath, $basePath.'/') !== 0) return $fallback;
        return $url;
    }

    public static function revokeOtherSessions($database, $userId)
    {
        if (function_exists('wbce_revoke_user_sessions')) {
            wbce_revoke_user_sessions((int) $userId, session_id());
            return;
        }
        self::execute($database, "DELETE FROM `{TP}dbsessions` WHERE `user`=" . (int) $userId
            . " AND `id`!='" . self::escape($database, session_id()) . "'");
    }
}
