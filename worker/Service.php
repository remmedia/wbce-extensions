<?php
require_once __DIR__.'/DatabaseCompatibility.php';
require_once __DIR__.'/Language.php';
require_once __DIR__ . '/CronExpression.php';
require_once __DIR__ . '/Registry.php';

final class WbceWorkerService
{
    private $database;

    public function __construct($database)
    {
        if (!is_object($database) || !method_exists($database, 'query')) { throw new RuntimeException(worker_t('database_unavailable')); }
        $this->database = $database;
        // Worker execution starts in a fresh PHP process. Load registrations
        // from all installed modules here as well, otherwise managed tasks
        // such as Log Rotate exist in the database but their callable cannot
        // be resolved when the scheduler executes them.
        require_once __DIR__.'/ModuleDiscovery.php';
        WbceWorkerModuleDiscovery::registerInstalledModules($this->database);
        $this->syncManagedTasks();
    }

    public function tasks(): array
    {
        $rows = array();
        $result = $this->database->query('SELECT * FROM `{TP}mod_worker_tasks` ORDER BY `name`, `id`');
        if ($result) { while ($row = $result->fetchRow()) {
            // Show the persisted value. Recomputing the next occurrence from
            // the current time hid overdue jobs and made a stalled scheduler
            // appear healthy in the backend.
            $row['next_run_display']=$this->displayUtc($row['next_run']); $row['timezone']=$this->timezoneLabel(); $rows[] = $row;
        } }
        return $rows;
    }

    public function logs(int $limit = 1000): array
    {
        $rows = array();
        $limit = max(1, min(1000, $limit));
        $result = $this->database->query('SELECT l.*, t.name AS task_name FROM `{TP}mod_worker_logs` l LEFT JOIN `{TP}mod_worker_tasks` t ON t.id=l.task_id ORDER BY l.id DESC LIMIT ' . $limit);
        if ($result) { while ($row = $result->fetchRow()) { $row['started_at_display']=$this->displayUtc($row['started_at']); $row['timezone']=$this->timezoneLabel(); $rows[] = $row; } }
        return $rows;
    }

    public function triggerLogs(int $limit = 100): array
    {
        $rows=array();$limit=max(1,min(250,$limit));
        $result=$this->database->query('SELECT * FROM `{TP}mod_worker_trigger_logs` ORDER BY `id` DESC LIMIT '.$limit);
        if($result){while($row=$result->fetchRow()){$row['called_at_display']=$this->displayUtc($row['called_at']);$row['finished_at_display']=$this->displayUtc($row['finished_at']);$rows[]=$row;}}
        return $rows;
    }

    public function save(array $input, bool $createdViaWorkerUi = false): int
    {
        $id = max(0, (int)($input['id'] ?? 0));
        $name = trim((string)($input['name'] ?? ''));
        $workerId = trim((string)($input['worker_id'] ?? ''));
        $cron = trim((string)($input['cron_expression'] ?? ''));
        $nameLength = function_exists('mb_strlen') ? mb_strlen($name, 'UTF-8') : strlen($name);
        if ($name === '' || $nameLength > 150) { throw new InvalidArgumentException(worker_t('invalid_name')); }
        $definition = WbceWorkerRegistry::get($workerId);
        if ($definition === null) { throw new InvalidArgumentException(worker_t('worker_unavailable')); }
        if (!empty($definition['managed_task'])) { throw new RuntimeException(worker_t('managed_task')); }
        $expression = new WbceCronExpression($cron);
        $configuration = trim((string)($input['configuration'] ?? '{}'));
        $decoded = json_decode($configuration === '' ? '{}' : $configuration, true);
        if (!is_array($decoded) || json_last_error() !== JSON_ERROR_NONE) { throw new InvalidArgumentException(worker_t('json_invalid')); }
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $next = $this->nextUtc($expression, $now);
        $values = array(
            'name' => $this->e($name), 'worker_id' => $this->e($workerId),
            'cron_expression' => $this->e($cron),
            'configuration' => $this->e(json_encode($decoded, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)),
            'managed' => $createdViaWorkerUi ? 0 : 1,
            'active' => !empty($input['active']) ? 1 : 0, 'next_run' => $next,
            'modified_at' => $now->format('Y-m-d H:i:s'),
        );
        if ($id > 0) {
            $existing = $this->task($id);
            if ($createdViaWorkerUi && $existing && !empty($existing['managed'])) { throw new RuntimeException(worker_t('managed_task')); }
            $values['id'] = $id;
            if (!$this->database->updateRow('{TP}mod_worker_tasks', 'id', $values)) { throw new RuntimeException($this->database->get_error()); }
            return $id;
        }
        $values['created_at'] = $values['modified_at'];
        if (!$this->database->insertRow('{TP}mod_worker_tasks', $values)) { throw new RuntimeException($this->database->get_error()); }
        // Use the connection's native insert id. A subsequent SELECT through
        // compatibility wrappers can run on a different result context on
        // older WBCE database adapters and return the wrong task id.
        $insertId=method_exists($this->database,'getLastInsertId')?(int)$this->database->getLastInsertId():(int)worker_db_value($this->database,'SELECT LAST_INSERT_ID()');
        if($insertId>0){$inserted=$this->task($insertId);if($inserted&&(string)$inserted['worker_id']===$workerId&&(string)$inserted['name']===$name&&(string)$inserted['created_at']===$values['created_at'])return $insertId;}
        // Some WBCE database adapters lose LAST_INSERT_ID() when an insert is
        // routed through their compatibility layer. Resolve this exact insert
        // deterministically instead of attaching module metadata to task 0.
        $resolved=(int)worker_db_value($this->database,"SELECT `id` FROM `{TP}mod_worker_tasks` WHERE `worker_id`='".$values['worker_id']."' AND `name`='".$values['name']."' AND `created_at`='".$values['created_at']."' ORDER BY `id` DESC LIMIT 1");
        if($resolved<1)throw new RuntimeException(worker_t('task_id_unresolved'));
        return $resolved;
    }

    public function toggle(int $id, bool $active, bool $fromWorkerUi = false): void
    {
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $row = $this->task($id);
        if (!$row) { throw new RuntimeException(worker_t('task_not_found')); }
        if ($fromWorkerUi && !empty($row['managed'])) { throw new RuntimeException(worker_t('managed_task')); }
        $next = $this->nextUtc(new WbceCronExpression($row['cron_expression']), $now);
        $result = $this->database->query("UPDATE `{TP}mod_worker_tasks` SET `active`=" . ($active ? 1 : 0) . ", `next_run`='" . $next . "', `modified_at`='" . $now->format('Y-m-d H:i:s') . "' WHERE `id`=" . $id);
        if ($result === false) { throw new RuntimeException(worker_t('database_write_failed')); }
    }

    public function delete(int $id, bool $fromWorkerUi = false): void
    {
        $row = $this->task($id);
        if (!$row) { throw new RuntimeException(worker_t('task_not_found')); }
        if ($fromWorkerUi && !empty($row['managed'])) { throw new RuntimeException(worker_t('managed_task')); }
        if($row['lock_token']!==null)throw new RuntimeException(worker_t('running_delete_denied'));
        $result = $this->database->query('DELETE FROM `{TP}mod_worker_tasks` WHERE `id`=' . $id . ' AND `lock_token` IS NULL');
        if ($result === false) { throw new RuntimeException(worker_t('database_write_failed')); }
        if ($this->task($id)) { throw new RuntimeException(worker_t('running_delete_denied')); }
        $result = $this->database->query('DELETE FROM `{TP}mod_worker_logs` WHERE `task_id`=' . $id);
        if ($result === false) { throw new RuntimeException(worker_t('database_write_failed')); }
    }

    public function due(int $limit = 100): array
    {
        $this->recoverStaleLocks();
        $now = gmdate('Y-m-d H:i:s'); $limit=max(1,min(100,$limit));
        $rows = array();
        $result = $this->database->query("SELECT * FROM `{TP}mod_worker_tasks` WHERE `active`=1 AND (`next_run` IS NULL OR `next_run`<='" . $now . "') AND `lock_token` IS NULL ORDER BY `next_run`, `id` LIMIT ".$limit);
        if ($result) { while ($row = $result->fetchRow()) { $rows[] = $row; } }
        return $rows;
    }

    public function claim(int $id, bool $manual = false, bool $fromWorkerUi = false): ?string
    {
        $row = $this->task($id);
        if ($manual && $fromWorkerUi && $row && !empty($row['managed'])) { throw new RuntimeException(worker_t('managed_task')); }
        if (!$row || (!$manual && !(int)$row['active']) || $row['lock_token'] !== null) { return null; }
        try { $token = bin2hex(random_bytes(32)); }
        catch (Throwable $exception) { throw new RuntimeException(worker_t('secure_token_failed'), 0, $exception); }
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $next = $this->nextUtc(new WbceCronExpression($row['cron_expression']), $now);
        $result = $this->database->query("UPDATE `{TP}mod_worker_tasks` SET `lock_token`='" . $token . "',`locked_at`='" . $now->format('Y-m-d H:i:s') . "',`next_run`='" . $next . "' WHERE `id`=" . $id . " AND `lock_token` IS NULL");
        if ($result === false) { throw new RuntimeException(worker_t('database_write_failed')); }
        return (string)worker_db_value($this->database, "SELECT `lock_token` FROM `{TP}mod_worker_tasks` WHERE `id`=" . $id . " AND `lock_token`='" . $token . "'") === $token ? $token : null;
    }

    public function execute(int $id, string $token): void
    {
        $row = $this->task($id);
        if (!$row || !hash_equals((string)$row['lock_token'], $token)) { throw new RuntimeException(worker_t('lock_invalid')); }
        $started = microtime(true); $startedAt = gmdate('Y-m-d H:i:s');
        $this->database->insertRow('{TP}mod_worker_logs', array('task_id'=>$id,'started_at'=>$startedAt,'status'=>'running'));
        $logId = method_exists($this->database,'getLastInsertId') ? (int)$this->database->getLastInsertId() : (int)worker_db_value($this->database, 'SELECT LAST_INSERT_ID()');
        if($logId<1){$logId=(int)worker_db_value($this->database,"SELECT `id` FROM `{TP}mod_worker_logs` WHERE `task_id`=".$id." AND `started_at`='".$this->e($startedAt)."' ORDER BY `id` DESC LIMIT 1");}
        if($logId<1)throw new RuntimeException(worker_t('log_id_unresolved'));
        $status = 'success'; $message = worker_t('task_completed');
        try {
            $definition = WbceWorkerRegistry::get($row['worker_id']);
            // Older Log Rotate installations can have a managed task from a
            // prior release while its definition was not loaded in this
            // isolated execution process. Load its installed registration
            // explicitly before declaring the task unavailable.
            if (!$definition && (string)$row['worker_id'] === 'logrotate.rotate') {
                $logrotateRegistration = WB_PATH . '/modules/logrotate/initialize.php';
                if (is_file($logrotateRegistration) && is_readable($logrotateRegistration)) {
                    include_once $logrotateRegistration;
                    $definition = WbceWorkerRegistry::get($row['worker_id']);
                }
            }
            if (!$definition) { throw new RuntimeException(worker_t('registered_unavailable')); }
            $configuration = json_decode((string)$row['configuration'], true) ?: array();
            $result = call_user_func($definition['callable'], $configuration, $row);
            if (is_array($result) && isset($result['message'])) { $message = (string)$result['message']; }
            elseif (is_string($result) && $result !== '') { $message = $result; }
        } catch (Throwable $exception) {
            $status = 'failed'; $message = $exception->getMessage();
        }
        $duration = max(0, (int)round((microtime(true) - $started) * 1000));
        $finished = gmdate('Y-m-d H:i:s');
        $this->database->query("UPDATE `{TP}mod_worker_logs` SET `finished_at`='".$finished."',`status`='".$status."',`message`='".$this->e($message)."',`duration_ms`=".$duration." WHERE `id`=".$logId);
        $this->database->query("UPDATE `{TP}mod_worker_tasks` SET `last_run`='".$finished."',`last_status`='".$status."',`last_message`='".$this->e($message)."',`locked_at`=NULL,`lock_token`=NULL WHERE `id`=".$id." AND `lock_token`='".$this->e($token)."'");
        $logCenter=WB_PATH.'/modules/log_center/Client.php';
        if(is_file($logCenter)){try{require_once $logCenter;if(class_exists('WbceLogCenterClient'))WbceLogCenterClient::sendEvent('worker',$status,(string)$row['worker_id'],$message);}catch(Throwable $ignored){}}
    }

    public function releaseFailedLaunch(int $id, string $token, string $message): void
    {
        $now=gmdate('Y-m-d H:i:s');
        $this->database->insertRow('{TP}mod_worker_logs',array('task_id'=>$id,'started_at'=>$now,'finished_at'=>$now,'status'=>'failed','message'=>$message,'duration_ms'=>0));
        $this->database->query("UPDATE `{TP}mod_worker_tasks` SET `last_status`='failed',`last_message`='".$this->e($message)."',`locked_at`=NULL,`lock_token`=NULL WHERE `id`=".$id." AND `lock_token`='".$this->e($token)."'");
        $logCenter=WB_PATH.'/modules/log_center/Client.php';
        if(is_file($logCenter)){try{require_once $logCenter;if(class_exists('WbceLogCenterClient'))WbceLogCenterClient::sendEvent('worker','failed',(string)($row['worker_id']??$id),$message);}catch(Throwable $ignored){}}
    }

    private function recoverStaleLocks(): void
    {
        // Long-running backups, pool replication and malware scans must never
        // be started a second time merely because they legitimately exceed
        // thirty minutes. Only recover locks that cannot plausibly still
        // belong to a live request.
        $before = gmdate('Y-m-d H:i:s', time() - 86400);
        $result = $this->database->query("SELECT `id`,`lock_token` FROM `{TP}mod_worker_tasks` WHERE `lock_token` IS NOT NULL AND `locked_at`<'".$before."'");
        while ($result && ($row = $result->fetchRow())) {
            $this->releaseFailedLaunch((int)$row['id'], (string)$row['lock_token'], worker_t('stale_lock_released'));
        }
    }

    private function task(int $id): ?array
    {
        $result = $this->database->query('SELECT * FROM `{TP}mod_worker_tasks` WHERE `id`=' . $id . ' LIMIT 1');
        return $result ? ($result->fetchRow() ?: null) : null;
    }
    private function syncManagedTasks(): void
    {
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        foreach (WbceWorkerRegistry::all() as $workerId => $definition) {
            if (empty($definition['managed_task'])) { continue; }
            $escapedWorkerId = $this->e($workerId);
            $existingId = (int)worker_db_value($this->database, "SELECT `id` FROM `{TP}mod_worker_tasks` WHERE `worker_id`='".$escapedWorkerId."' ORDER BY `id` LIMIT 1");
            if ($existingId > 0) {
                $cron = (string)$definition['default_cron'];
                try { $next = $this->nextUtc(new WbceCronExpression($cron), $now); }
                catch (Throwable $exception) {
                    error_log('WBCE Worker: invalid managed schedule for '.$workerId.': '.$exception->getMessage());
                    continue;
                }
                // Managed tasks are owned by their registering module. Their
                // schedule and active state therefore follow that module on
                // every update; runtime configuration remains untouched.
                $escapedCron=$this->e($cron);$escapedNext=$this->e($next);
                $this->database->query("UPDATE `{TP}mod_worker_tasks` SET `next_run`=CASE WHEN `cron_expression`<>'".$escapedCron."' OR `active`<>1 OR `next_run` IS NULL THEN '".$escapedNext."' ELSE `next_run` END,`managed`=1,`active`=1,`cron_expression`='".$escapedCron."',`modified_at`='".$now->format('Y-m-d H:i:s')."' WHERE `worker_id`='".$escapedWorkerId."' AND (`managed`<>1 OR `active`<>1 OR `cron_expression`<>'".$escapedCron."' OR `next_run` IS NULL)");
                continue;
            }
            $cron = (string)$definition['default_cron'];
            try { $expression = new WbceCronExpression($cron); }
            catch (Throwable $exception) {
                error_log('WBCE Worker: invalid managed schedule for '.$workerId.': '.$exception->getMessage());
                continue;
            }
            $configuration = json_encode($definition['default_configuration'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $this->database->insertRow('{TP}mod_worker_tasks', array(
                'name'=>$this->e((string)$definition['default_name']), 'worker_id'=>$escapedWorkerId,
                'cron_expression'=>$this->e($cron), 'configuration'=>$this->e($configuration ?: '{}'),
                'managed'=>1, 'active'=>1,
                'next_run'=>$this->nextUtc($expression, $now),
                'created_at'=>$now->format('Y-m-d H:i:s'), 'modified_at'=>$now->format('Y-m-d H:i:s'),
            ));
        }
    }
    private function e(string $value): string { return $this->database->escapeString($value); }
    private function timezone(): DateTimeZone
    {
        global $admin;
        $userId = isset($admin) && is_object($admin) && method_exists($admin, 'get_user_id') ? (int)$admin->get_user_id() : (isset($_SESSION['USER_ID']) ? (int)$_SESSION['USER_ID'] : 0);
        if ($userId > 0) {
            $value = worker_db_value($this->database, 'SELECT `timezone` FROM `{TP}users` WHERE `user_id`='.$userId);
            if (is_string($value) && trim($value) !== '') {
                $value = trim($value);
                if (function_exists('wbce_timezone')) {
                    try { return wbce_timezone($value); } catch (Throwable $ignored) { }
                }
                if (is_numeric($value)) {
                    $numeric = (float)$value;
                    if (is_finite($numeric) && abs($numeric) <= 50400) {
                        $seconds = (int)$numeric; $sign = $seconds < 0 ? '-' : '+'; $absolute = abs($seconds);
                        try { return new DateTimeZone(sprintf('%s%02d:%02d', $sign, intdiv($absolute, 3600), intdiv($absolute % 3600, 60))); } catch (Throwable $ignored) { }
                    }
                } else { try { return new DateTimeZone($value); } catch (Throwable $ignored) { } }
            }
        }
        if (function_exists('wbce_timezone')) {
            try { $timezone=wbce_timezone(); if($timezone instanceof DateTimeZone)return $timezone; } catch (Throwable $ignored) { }
        }
        if (defined('TIMEZONE_IDENTIFIER')) {
            try { return new DateTimeZone((string)TIMEZONE_IDENTIFIER); } catch (Throwable $ignored) { }
        }
        return new DateTimeZone(date_default_timezone_get());
    }
    private function timezoneLabel(): string
    {
        return $this->timezone()->getName();
    }
    private function nextUtc(WbceCronExpression $expression, DateTimeImmutable $utcNow): string
    {
        return $expression->next($utcNow->setTimezone($this->timezone()))->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
    }
    private function displayUtc($value): string
    {
        if (!$value) { return ''; }
        $date = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', (string)$value, new DateTimeZone('UTC'));
        return $date ? $date->setTimezone($this->timezone())->format('d.m.Y H:i:s') : (string)$value;
    }
}
