<?php
defined('WB_PATH') or die('No direct access');
require_once __DIR__.'/src/MailerRegistry.php';
if(function_exists('wbce_add_filter'))wbce_add_filter('mail.transport',static function($result,$message,$mailer){$custom=WbceMailerRegistry::send((array)$message+['mailer'=>$mailer]);return !empty($custom['handled'])?$custom:$result;},1);
