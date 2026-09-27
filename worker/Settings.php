<?php
require_once __DIR__.'/DatabaseCompatibility.php';
require_once __DIR__.'/Language.php';
final class WbceWorkerSettings
{
    public static function install($database): void
    {
        $database->query("CREATE TABLE IF NOT EXISTS `{TP}mod_worker_settings` (`name` varchar(50) NOT NULL, `value` varchar(255) NOT NULL, PRIMARY KEY (`name`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $database->query("CREATE TABLE IF NOT EXISTS `{TP}mod_worker_trigger_logs` (`id` bigint unsigned NOT NULL AUTO_INCREMENT, `source` varchar(20) NOT NULL, `called_at` datetime NOT NULL, `finished_at` datetime DEFAULT NULL, `status` varchar(20) NOT NULL, `started_tasks` int unsigned NOT NULL DEFAULT 0, `message` text DEFAULT NULL, PRIMARY KEY (`id`), KEY `source_called` (`source`,`called_at`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $database->query("INSERT IGNORE INTO `{TP}mod_worker_settings` (`name`,`value`) VALUES ('trigger_mode','pageview')");
        $database->query("INSERT IGNORE INTO `{TP}mod_worker_settings` (`name`,`value`) VALUES ('pageview_interval','5'),('url_interval','3'),('cron_interval','1')");
        $database->query("INSERT IGNORE INTO `{TP}mod_worker_settings` (`name`,`value`) VALUES ('max_tasks_per_run','2'),('run_budget_seconds','20'),('pause_between_tasks_ms','100')");
        if (self::get($database, 'url_token', '') === '') { self::renewToken($database); }
    }

    public static function get($database, string $name, string $default = ''): string
    {
        $value = worker_db_value($database, "SELECT `value` FROM `{TP}mod_worker_settings` WHERE `name`='".$database->escapeString($name)."'");
        return $value === null || $value === false ? $default : (string)$value;
    }

    public static function mode($database): string
    {
        $mode = self::get($database, 'trigger_mode', 'pageview');
        return in_array($mode, array('cron','url','pageview'), true) ? $mode : 'pageview';
    }

    public static function setMode($database, string $mode): void
    {
        if (!in_array($mode, array('cron','url','pageview'), true)) { throw new InvalidArgumentException(worker_t('invalid_mode')); }
        self::set($database, 'trigger_mode', $mode);
    }

    public static function pageviewInterval($database): int
    {
        return self::interval($database, 'pageview');
    }

    public static function setPageviewInterval($database, int $minutes): void
    {
        self::setInterval($database, 'pageview', $minutes);
    }

    public static function intervalOptions(string $source): array
    {
        if ($source === 'cron') { return array(1,2,3,4,5); }
        if ($source === 'url') { return array(1,2,3,4,5,10,15); }
        return array(1,2,3,4,5,10,15,30,60);
    }

    public static function interval($database, string $source): int
    {
        $defaults = array('cron'=>1, 'url'=>3, 'pageview'=>5);
        if (!isset($defaults[$source])) { return 1; }
        $value = (int)self::get($database, $source.'_interval', (string)$defaults[$source]);
        return in_array($value, self::intervalOptions($source), true) ? $value : $defaults[$source];
    }

    public static function setInterval($database, string $source, int $minutes): void
    {
        if (!in_array($minutes, self::intervalOptions($source), true)) { throw new InvalidArgumentException(worker_t('invalid_interval')); }
        self::set($database, $source.'_interval', (string)$minutes);
    }

    public static function limits($database): array
    {
        return array('max_tasks'=>max(1,min(20,(int)self::get($database,'max_tasks_per_run','2'))),'budget_seconds'=>max(5,min(300,(int)self::get($database,'run_budget_seconds','20'))),'pause_ms'=>max(0,min(5000,(int)self::get($database,'pause_between_tasks_ms','100'))));
    }
    public static function saveLimits($database, array $input): void
    {
        $max=(int)($input['max_tasks_per_run']??2);$budget=(int)($input['run_budget_seconds']??20);$pause=(int)($input['pause_between_tasks_ms']??100);
        if($max<1||$max>20||$budget<5||$budget>300||$pause<0||$pause>5000)throw new InvalidArgumentException(worker_t('invalid_limits'));
        self::set($database,'max_tasks_per_run',(string)$max);self::set($database,'run_budget_seconds',(string)$budget);self::set($database,'pause_between_tasks_ms',(string)$pause);
    }
    public static function claimDispatch($database): ?string
    {
        $now=time();try{$token=$now.':'.bin2hex(random_bytes(8));}catch(Throwable $exception){return null;}
        $result=$database->query("INSERT INTO `{TP}mod_worker_settings` (`name`,`value`) VALUES ('dispatch_running','".$database->escapeString($token)."') ON DUPLICATE KEY UPDATE `value`=IF(CAST(SUBSTRING_INDEX(`value`,':',1) AS UNSIGNED)<=".($now-300).",VALUES(`value`),`value`)");
        return $result!==false&&hash_equals($token,self::get($database,'dispatch_running',''))?$token:null;
    }
    public static function releaseDispatch($database, string $token): void
    {
        $database->query("DELETE FROM `{TP}mod_worker_settings` WHERE `name`='dispatch_running' AND `value`='".$database->escapeString($token)."'");
    }

    public static function claimPageviewTrigger($database): ?string
    {
        $now = time();
        $threshold = $now - (self::pageviewInterval($database) * 60);
        $last = strtotime(self::get($database, 'last_trigger_pageview', ''));
        if ($last !== false && $last > $threshold) { return null; }
        try { $claim = $now.':'.bin2hex(random_bytes(8)); }
        catch (Throwable $exception) { return null; }
        // Keep a separate live lock until the detached page-view run has
        // finished. The interval marker alone prevents frequent starts but
        // used to allow overlapping runs once a slow dispatch exceeded it.
        // A PHP timeout can prevent the finally block from releasing this
        // marker. A stale run must not disable the page-view cron for a day.
        $limits = self::limits($database);
        $lockSeconds = max(300, min(900, $limits['budget_seconds'] + (self::pageviewInterval($database) * 60) + 60));
        $result = $database->query("INSERT INTO `{TP}mod_worker_settings` (`name`,`value`) VALUES ('pageview_running','".$database->escapeString($claim)."') ON DUPLICATE KEY UPDATE `value`=IF(CAST(SUBSTRING_INDEX(`value`,':',1) AS UNSIGNED)<=".($now-$lockSeconds).",VALUES(`value`),`value`)");
        if ($result === false) { return null; }
        $stored = self::get($database, 'pageview_running', '');
        return hash_equals($claim, $stored) ? $claim : null;
    }

    public static function releasePageviewTrigger($database, string $claim): void
    {
        $database->query("DELETE FROM `{TP}mod_worker_settings` WHERE `name`='pageview_running' AND `value`='".$database->escapeString($claim)."'");
    }

    public static function renewToken($database): string
    {
        try { $token = bin2hex(random_bytes(32)); }
        catch (Throwable $exception) { throw new RuntimeException(worker_t('secure_token_failed'), 0, $exception); }
        self::set($database, 'url_token', $token);
        return $token;
    }

    public static function markTrigger($database, string $source): void
    {
        if (in_array($source, array('cron','url','pageview'), true)) {
            self::set($database, 'last_trigger_'.$source, gmdate('Y-m-d H:i:s'));
        }
    }

    public static function beginTrigger($database, string $source): int
    {
        if (!in_array($source, array('cron','url','pageview'), true)) { return 0; }
        self::markTrigger($database, $source);
        $calledAt = gmdate('Y-m-d H:i:s');
        $result = $database->query("INSERT INTO `{TP}mod_worker_trigger_logs` (`source`,`called_at`,`status`) VALUES ('".$database->escapeString($source)."','".$calledAt."','running')");
        if ($result === false) { return 0; }
        $id = method_exists($database, 'getLastInsertId') ? (int)$database->getLastInsertId() : (int)worker_db_value($database, 'SELECT LAST_INSERT_ID()');
        if ($id < 1) {
            $id = (int)worker_db_value($database, "SELECT `id` FROM `{TP}mod_worker_trigger_logs` WHERE `source`='".$database->escapeString($source)."' AND `called_at`='".$calledAt."' ORDER BY `id` DESC LIMIT 1");
        }
        return $id;
    }

    public static function finishTrigger($database, int $id, array $result): void
    {
        if ($id < 1) { return; }
        $errors = isset($result['errors']) && is_array($result['errors']) ? $result['errors'] : array();
        $status = $errors ? 'failed' : (!empty($result['skipped']) ? 'skipped' : 'success');
        $message = $errors ? implode(' | ', $errors) : worker_t(!empty($result['skipped']) ? 'heartbeat_skipped' : 'heartbeat_success');
        $database->query("UPDATE `{TP}mod_worker_trigger_logs` SET `finished_at`='".gmdate('Y-m-d H:i:s')."',`status`='".$status."',`started_tasks`=".(int)(isset($result['started']) ? $result['started'] : 0).",`message`='".$database->escapeString($message)."' WHERE `id`=".$id);
    }

    public static function updateTriggerStage($database, int $id, string $message): void
    {
        if($id<1)return;
        $database->query("UPDATE `{TP}mod_worker_trigger_logs` SET `message`='".$database->escapeString(substr($message,0,2000))."' WHERE `id`=".$id." AND `finished_at` IS NULL");
    }

    public static function lastTrigger($database, string $source = ''): string
    {
        $sourceSql = in_array($source, array('cron','url','pageview'), true)
            ? " WHERE `source`='".$database->escapeString($source)."'"
            : '';
        $logged = worker_db_value($database, 'SELECT MAX(`called_at`) FROM `{TP}mod_worker_trigger_logs`'.$sourceSql);
        if ($logged) { return (string)$logged; }
        if ($sourceSql !== '') {
            return self::get($database, 'last_trigger_'.$source, '');
        }
        $latest = '';
        foreach (array('cron','url','pageview') as $triggerSource) {
            $value = self::get($database, 'last_trigger_'.$triggerSource, '');
            if ($value > $latest) { $latest = $value; }
        }
        return $latest;
    }

    private static function set($database, string $name, string $value): void
    {
        $result = $database->query("INSERT INTO `{TP}mod_worker_settings` (`name`,`value`) VALUES ('".$database->escapeString($name)."','".$database->escapeString($value)."') ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)");
        if ($result === false) { throw new RuntimeException(worker_t('database_write_failed')); }
    }
}
