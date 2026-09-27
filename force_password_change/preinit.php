<?php
defined('WB_PATH') or die('No direct access');

// The bridge is a declared dependency. Load its API explicitly because WBCE
// 1.6.8 does not guarantee module pre-initializer order.
if (!function_exists('wbce_add_action')) {
    $bridge = WB_PATH . '/modules/wbce_hook_bridge/preinit.php';
    if (is_file($bridge)) require_once $bridge;
}
if (function_exists('wbce_add_action')) require_once __DIR__ . '/initialize.php';
