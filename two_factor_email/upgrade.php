<?php
defined('WB_PATH') or die('No direct access');
require_once __DIR__.'/Service.php';
WbceEmailFactorService::install($database);
