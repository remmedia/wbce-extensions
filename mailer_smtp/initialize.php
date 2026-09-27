<?php
defined('WB_PATH')or die('No direct access');
$core=WB_PATH.'/modules/mailer/src/MailerRegistry.php';if(is_file($core))require_once $core;
if(function_exists('wbce_add_filter')){
    wbce_add_filter('mailer.providers',static function($providers){$providers['smtp']=['name'=>'SMTP','description'=>'Universeller, verpflichtender SMTP-Fallback.','module'=>'mailer_smtp','protected'=>true,'send'=>static fn($message,$settings)=>['handled'=>false]];return $providers;});
    wbce_add_filter('addon.beforeUninstall',static function($allowed,$context=null){if(is_array($allowed)&&$context===null){$context=$allowed;$allowed=true;}if(is_array($context)&&($context['type']??'')==='module'&&($context['directory']??'')==='mailer_smtp')return false;return $allowed;},1);
}
