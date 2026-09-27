<?php
if (!defined('WB_PATH')) { return; }
require_once __DIR__.'/Language.php';
require_once __DIR__.'/src/AuthenticationProviderInterface.php';
require_once __DIR__.'/src/AuthenticationProviderManager.php';

if (!function_exists('wbce_add_action')) {
    define('WBCE_HOOK_BRIDGE_EMULATES_HOOKS', true);
    require_once __DIR__ . '/src/HookDispatcher.php';
} elseif (!defined('WBCE_HOOK_BRIDGE_EMULATES_HOOKS')) {
    define('WBCE_HOOK_BRIDGE_EMULATES_HOOKS', false);
}

require_once __DIR__ . '/src/HookBridge.php';
