<?php
/**
 * @category        modules
 * @package         Secure Form Switcher
 * @author          WBCE Project
 * @copyright       Norbert Heimsath
 * @license			WTFPL
 */

//no direct file access
if (count(get_included_files())==1) {
    header("Location: ../index.php", true, 301);
}

require __DIR__.'/languages/EN.php';
if(defined('LANGUAGE')&&LANGUAGE!=='EN'&&preg_match('/^[A-Z]{2}$/D',(string)LANGUAGE)&&is_readable(__DIR__.'/languages/'.LANGUAGE.'.php'))require __DIR__.'/languages/'.LANGUAGE.'.php';
$settingNames=array("wb_secform_secret","wb_secform_secrettime","wb_secform_timeout","wb_secform_tokenname","wb_secform_usefp","fingerprint_with_ip_octets");
$quoted=array();
foreach($settingNames as $settingName)$quoted[]="'".$database->escapeString($settingName)."'";
$result=$database->query("DELETE FROM `{TP}settings` WHERE `name` IN (".implode(',',$quoted).")");
if($result===false||$database->is_error())throw new RuntimeException($SFS['UNINSTALL_FAILED']);
foreach($settingNames as $settingName)unset(Settings::$aSettings[$settingName]);
