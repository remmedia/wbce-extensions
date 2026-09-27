<?php
defined('WB_PATH') or die('No direct access');
/** Performs the former Log Rotate task as a managed Log Center worker. */
final class WbceLogCenterMaintenance
{
    public static function run(array $configuration = array(), array $task = array()): array
    {
        $limit = isset($configuration['max_size_mb']) ? (int)$configuration['max_size_mb'] : (int)self::setting('log_center_max_log_size_mb', 3);
        $limit = max(1, min(1024, $limit));
        return array('message' => self::rotate($limit) ? log_center_text('ROTATION_DONE') : log_center_text('ROTATION_IDLE'));
    }
    public static function rotate(int $limitMb = 3): bool
    {
        $file = WB_PATH.'/var/logs/php_error.log.php';
        $root = realpath(WB_PATH.'/var/logs'); $path = realpath($file);
        if ($root === false || $path === false || !is_file($path) || strpos($path, rtrim($root,DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR) !== 0) return false;
        clearstatcache(true, $path); $size = @filesize($path);
        if ($size === false || $size <= max(1,$limitMb) * 1048576) return false;
        try { $zone = function_exists('wbce_timezone') ? wbce_timezone() : new DateTimeZone(date_default_timezone_get()); } catch (Throwable $e) { $zone = new DateTimeZone('UTC'); }
        $target = dirname($path).DIRECTORY_SEPARATOR.(new DateTimeImmutable('now',$zone))->format('Ymd_His').'_php_error.log.php';
        return @rename($path, $target);
    }
    private static function setting($name, $default) { try { return class_exists('Settings') ? Settings::GetDb($name,$default) : $default; } catch (Throwable $e) { return $default; } }
}
