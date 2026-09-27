<?php
defined('WB_PATH') or die('Access denied');
require_once __DIR__.'/Database.php';
require_once __DIR__.'/Service.php';
WbceSecurityCenterService::install(wbce_security_center_database($database??null));
if (method_exists('WbceSecurityCenterService', 'upgradeSchema')) {
    WbceSecurityCenterService::upgradeSchema(wbce_security_center_database($database??null));
}
$securityCenterUpgradeDb=wbce_security_center_database($database??null);
$securityCenterUpgradeResult=$securityCenterUpgradeDb->query("UPDATE `{TP}addons` SET `function`='tool,preinit,initialize', `version`='1.0.72' WHERE `directory`='security_center' AND `type`='module'");
if($securityCenterUpgradeResult===false||(is_object($securityCenterUpgradeResult)&&method_exists($securityCenterUpgradeResult,'error')&&(string)$securityCenterUpgradeResult->error()!==''))throw new RuntimeException('Die systemweite Security-Center-Ausführung konnte nicht registriert werden.');
