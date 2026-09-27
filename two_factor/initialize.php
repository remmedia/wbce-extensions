<?php
defined('WB_PATH') or die('No direct access');
require_once __DIR__.'/Compatibility.php';
require_once __DIR__.'/Registry.php';
require_once __DIR__.'/ChallengeView.php';
require_once __DIR__.'/Settings.php';
require_once __DIR__.'/UserSection.php';
if(class_exists('WbceHookBridge'))WbceHookBridge::enablePreferencesHooks();
if(function_exists('wbce_add_filter'))wbce_add_filter('user.preferences.sections',function($sections,$userId)use($database){$html=WbceTwoFactorUserSection::render($database,(int)$userId);if($html!=='')$sections[]=$html;return $sections;},5);
if(function_exists('wbce_add_filter'))wbce_add_filter('addon.beforeUninstall',function($allowed,$context)use($database){
    if($allowed===false||!is_array($context)||($context['type']??'')!=='module')return $allowed;
    $directory=(string)($context['directory']??'');
    if($directory==='two_factor'&&WbceTwoFactorRegistry::installedProviderModules($database)!==array())return false;
    if(WbceTwoFactorRegistry::providerIdForModule($directory)!==null&&!WbceTwoFactorRegistry::canUninstallModule($database,$directory))return false;
    return $allowed;
},1);
