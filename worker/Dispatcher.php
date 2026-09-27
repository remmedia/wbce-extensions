<?php
require_once __DIR__.'/Service.php';
require_once __DIR__.'/AsyncLauncher.php';

final class WbceWorkerDispatcher
{
    public static function dispatch($database, $progress = null, bool $executeInline = false): array
    {
        $service = new WbceWorkerService($database); $started = 0; $errors = array(); $skipped=false;
        $dispatchToken=WbceWorkerSettings::claimDispatch($database);
        if($dispatchToken===null)return array('started'=>0,'errors'=>array(),'skipped'=>true);
        $limits=WbceWorkerSettings::limits($database);$deadline=microtime(true)+$limits['budget_seconds'];
        if(is_callable($progress))call_user_func($progress,worker_t('trace_due'));
        try { foreach ($service->due($limits['max_tasks']) as $task) {
            // Process the complete due queue. In page-view mode this runs only
            // after the response has been detached from the visitor.
            if(microtime(true)>=$deadline){$skipped=true;break;}$token = null;
            try {
                if(is_callable($progress))call_user_func($progress,worker_t('trace_claim',array('{id}'=>(string)(int)$task['id'],'{worker}'=>(string)$task['worker_id'])));
                $token = $service->claim((int)$task['id']);
                if ($token !== null) {
                    if(is_callable($progress))call_user_func($progress,worker_t('trace_launch',array('{id}'=>(string)(int)$task['id'],'{worker}'=>(string)$task['worker_id'])));
                    if ($executeInline) {
                        // The website response has already been detached (or
                        // this dispatcher itself runs in a detached internal
                        // request), so no second loopback request is needed.
                        $service->execute((int)$task['id'], $token);
                    } else {
                        WbceWorkerAsyncLauncher::launch((int)$task['id'], $token);
                    }
                    $started++;
                    if($limits['pause_ms']>0&&$started<$limits['max_tasks'])usleep($limits['pause_ms']*1000);
                }
            } catch (Throwable $exception) {
                if (is_string($token)) { $service->releaseFailedLaunch((int)$task['id'], $token, $exception->getMessage()); }
                $errors[] = '#'.(int)$task['id'].': '.$exception->getMessage();
            }
        }} finally { WbceWorkerSettings::releaseDispatch($database,$dispatchToken); }
        return array('started'=>$started, 'errors'=>$errors, 'skipped'=>$skipped);
    }
}
