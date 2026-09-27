<?php
defined('WB_PATH') or die('Access denied');
require_once __DIR__.'/Database.php';
require_once __DIR__.'/Service.php';
WbceSecurityCenterService::install(wbce_security_center_database($database??null));
$securityCenterInstallDb=wbce_security_center_database($database??null);
$securityCenterInstallResult=$securityCenterInstallDb->query("UPDATE `{TP}addons` SET `function`='tool,preinit,initialize', `version`='1.0.72' WHERE `directory`='security_center' AND `type`='module'");
if($securityCenterInstallResult===false||(is_object($securityCenterInstallResult)&&method_exists($securityCenterInstallResult,'error')&&(string)$securityCenterInstallResult->error()!==''))throw new RuntimeException('Die systemweite Security-Center-Ausführung konnte nicht registriert werden.');
