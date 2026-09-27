<?php
defined('WB_PATH') or die('Access denied');

final class WbceWorkerModuleDiscovery
{
    private static $loaded = false;

    /**
     * Loads worker definitions from installed modules. Merely present module
     * directories are deliberately ignored. On WBCE versions with the active
     * column, disabled modules do not receive runnable tasks.
     */
    public static function registerInstalledModules($database): array
    {
        if (self::$loaded) return array();
        self::$loaded = true;
        $loaded = array();
        $activeSql = method_exists($database, 'field_exists') && $database->field_exists('{TP}addons', 'active') ? ' AND `active`=1' : '';
        $result = $database->query("SELECT `directory` FROM `{TP}addons` WHERE `type`='module'".$activeSql." ORDER BY `directory`");
        while ($result && ($row = $result->fetchRow())) {
            $directory = strtolower(trim((string)($row['directory'] ?? '')));
            if ($directory === '' || $directory === 'worker' || !preg_match('/^[a-z0-9][a-z0-9_-]{0,188}$/', $directory)) continue;
            $file = WB_PATH.'/modules/'.$directory.'/initialize.php';
            if (!is_file($file) || !is_readable($file)) continue;
            try {
                (static function ($registrationFile) { global $database; include_once $registrationFile; })($file);
                $loaded[] = $directory;
            } catch (Throwable $exception) {
                error_log('WBCE Worker module discovery ['.$directory.']: '.$exception->getMessage());
            }
        }
        return $loaded;
    }
}
