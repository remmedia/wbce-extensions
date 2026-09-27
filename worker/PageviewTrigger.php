<?php
require_once __DIR__.'/Settings.php';

final class WbceWorkerPageviewTrigger
{
    private static bool $registered = false;

    public static function register(): void
    {
        if (self::$registered) {
            return;
        }
        self::$registered = true;

        register_shutdown_function(static function (): void {
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_write_close();
            }
            try {
                global $database;
                if (WbceWorkerSettings::mode($database) !== 'pageview') {
                    return;
                }
                $pageviewClaim = WbceWorkerSettings::claimPageviewTrigger($database);
                if ($pageviewClaim === null) {
                    return;
                }

                // A second HTTP request to this installation is unreliable on
                // many shared hosts. Run after the response in this request.
                ignore_user_abort(true);
                @set_time_limit(0);
                self::finishResponse();
                try {
                    self::dispatch($database);
                } finally {
                    WbceWorkerSettings::releasePageviewTrigger($database, $pageviewClaim);
                }
            } catch (Throwable $exception) {
                error_log('WBCE Worker page view trigger: '.$exception->getMessage());
            }
        });
    }

    private static function dispatch($database): void
    {
        $triggerLogId = WbceWorkerSettings::beginTrigger($database, 'pageview');
        require_once __DIR__.'/Dispatcher.php';
        $triggerResult = WbceWorkerDispatcher::dispatch($database, null, true);
        WbceWorkerSettings::finishTrigger($database, $triggerLogId, $triggerResult);
    }

    private static function finishResponse(): void
    {
        if (function_exists('fastcgi_finish_request')) {
            @fastcgi_finish_request();
            return;
        }
        if (!headers_sent()) { header('Connection: close'); }
        while (ob_get_level() > 0) { @ob_end_flush(); }
        @flush();
    }

}
