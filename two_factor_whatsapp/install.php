<?php
defined('WB_PATH') or die('No direct access');
$installLanguage=defined('LANGUAGE')&&is_file(__DIR__.'/languages/'.strtoupper((string)LANGUAGE).'.php')?strtoupper((string)LANGUAGE):'EN';$installTexts=(array)require __DIR__.'/languages/'.$installLanguage.'.php';
if(!is_object($database)||!is_callable(array($database,'query')))throw new RuntimeException($installTexts['database_unavailable']);
$core=$database->query("SELECT directory FROM {TP}addons WHERE type='module' AND directory='two_factor' LIMIT 1");
$coreRow=null;if(is_object($core)){if(is_callable(array($core,'fetchRow')))$coreRow=$core->fetchRow(defined('MYSQLI_ASSOC')?MYSQLI_ASSOC:1);elseif(is_callable(array($core,'fetch_assoc')))$coreRow=$core->fetch_assoc();}
if(!is_array($coreRow))throw new RuntimeException($installTexts['provider_requires_core']);
require_once WB_PATH.'/modules/two_factor/Language.php';
require_once WB_PATH.'/modules/two_factor/Settings.php';
require_once __DIR__.'/Service.php';
WbceWhatsAppFactorService::install($database);
if(WbceTwoFactorSettings::selected($database)==='')WbceTwoFactorSettings::set($database,'selected_provider','whatsapp');
