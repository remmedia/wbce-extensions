<?php
/**
 * @category        modules
 * @package         maintainance_mode
 * @author          WBCE Project
 * @copyright       Norbert Heimsath
 * @license			WTFPL
 */

//no direct file access
if(count(get_included_files())==1){header("Location: ../index.php",TRUE,301);exit;}

// Never leave the public site locked in maintenance mode after removing its UI.
require __DIR__.'/languages/EN.php';
if(defined('LANGUAGE')&&LANGUAGE!=='EN'&&preg_match('/^[A-Z]{2}$/D',(string)LANGUAGE)&&is_readable(__DIR__.'/languages/'.LANGUAGE.'.php'))require __DIR__.'/languages/'.LANGUAGE.'.php';
$error=Settings::Set('wb_maintainance_mode',false);
if($error)throw new RuntimeException($MOD_MAINTAINANCE['UNINSTALL_FAILED']);
$deleteResult=$database->query("DELETE FROM `{TP}settings` WHERE `name`='wb_maintainance_mode'");
if($deleteResult===false||$database->is_error())throw new RuntimeException($MOD_MAINTAINANCE['UNINSTALL_FAILED']);
unset(Settings::$aSettings['wb_maintainance_mode']);
