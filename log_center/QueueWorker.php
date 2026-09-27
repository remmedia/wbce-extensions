<?php
defined('WB_PATH') or die('No direct access');
require_once __DIR__.'/Client.php';
require_once __DIR__.'/Language.php';
final class WbceLogCenterQueueWorker
{
    public static function run(array $configuration=array(),array $task=array()): array
    {
        return array('message'=>log_center_text(WbceLogCenterClient::flushQueue()?'QUEUE_SENT':'QUEUE_RETAINED'));
    }
}
