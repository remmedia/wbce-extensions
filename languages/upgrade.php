<?php
defined('WB_PATH') or die('No direct access');
@mkdir(WB_PATH . '/var/modules/languages', 0755, true);
if (is_file(WB_PATH . '/modules/worker/Service.php')) { require_once WB_PATH . '/modules/worker/Service.php'; new WbceWorkerService($database); }
