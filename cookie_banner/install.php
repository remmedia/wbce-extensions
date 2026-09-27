<?php
defined('WB_PATH') or die('No direct access');
require_once __DIR__ . '/CookieBanner.php';
if (class_exists('Settings') && Settings::get('cookie_banner', null) === null) Settings::set('cookie_banner', json_encode(WbceCookieBanner::defaults(), JSON_UNESCAPED_UNICODE));
