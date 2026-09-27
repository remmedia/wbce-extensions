<?php
defined('WB_PATH') or die('No direct access');
require_once __DIR__.'/CookieBanner.php';
if(function_exists('wbce_add_filter')) wbce_add_filter('frontend.page.output',static function($html){return WbceCookieBanner::inject((string)$html);});
