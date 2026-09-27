<?php
final class WbceWorkerRegistry
{
    private static array $compatibilityDefinitions = array();

    public static function register(string $id, array $definition): void
    {
        self::$compatibilityDefinitions[$id] = $definition;
    }

    public static function all(): array
    {
        $definitions = self::$compatibilityDefinitions;
        if (function_exists('wbce_apply_array_filters')) {
            $definitions = wbce_apply_array_filters('worker.definitions', $definitions);
        }
        foreach ($definitions as $id => $definition) {
            if (!is_string($id) || !preg_match('/^[a-z0-9][a-z0-9._-]{1,189}$/i', $id)
                || !is_array($definition) || !isset($definition['callable'])
                || !is_callable($definition['callable'])) {
                unset($definitions[$id]);
                continue;
            }
            $definitions[$id]['label'] = (string)($definition['label'] ?? $id);
            $definitions[$id]['description'] = (string)($definition['description'] ?? '');
            $definitions[$id]['default_cron'] = trim((string)($definition['default_cron'] ?? '0 * * * *'));
            $definitions[$id]['show_tasks'] = !isset($definition['show_tasks']) || (bool)$definition['show_tasks'];
            $definitions[$id]['show_process'] = !empty($definition['show_process']);
            $definitions[$id]['managed_task'] = !empty($definition['managed_task']);
            $definitions[$id]['default_name'] = trim((string)($definition['default_name'] ?? $definitions[$id]['label']));
            $definitions[$id]['default_configuration'] = is_array($definition['default_configuration'] ?? null)
                ? $definition['default_configuration'] : array();
        }
        ksort($definitions);
        return $definitions;
    }

    public static function get(string $id): ?array
    {
        $all = self::all();
        return $all[$id] ?? null;
    }
}
