<?php
defined('WB_PATH') or die('No direct access');
final class WbceLogCenterRegistry
{
    public static function all()
    {
        static $types;
        if (is_array($types)) return $types;
        $types = array(
            'error' => array(
                'type' => 'error',
                'label' => log_center_text('ERROR'),
                'description' => log_center_text('ERROR_DESCRIPTION'),
            ),
            'runtime' => array(
                'type' => 'runtime',
                'label' => log_center_text('RUNTIME_SERVICE'),
                'description' => log_center_text('RUNTIME_SERVICE_DESCRIPTION'),
            ),
        );
        foreach ((array)glob(WB_PATH.'/modules/*/log_center.php') as $file) {
            $definition = include $file;
            if (!is_array($definition)) continue;
            $id = strtolower(trim((string)($definition['type'] ?? '')));
            if (!preg_match('/^[a-z][a-z0-9_-]{0,39}$/', $id)) continue;
            $types[$id] = array(
                'type' => $id,
                'label' => (string)($definition['label'] ?? $id),
                'description' => (string)($definition['description'] ?? ''),
            );
        }
        ksort($types);
        return $types;
    }
    public static function has($type) { return isset(self::all()[strtolower((string)$type)]); }
}
