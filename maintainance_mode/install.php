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

require __DIR__.'/languages/EN.php';
if(defined('LANGUAGE')&&LANGUAGE!=='EN'&&preg_match('/^[A-Z]{2}$/D',(string)LANGUAGE)&&is_readable(__DIR__.'/languages/'.LANGUAGE.'.php'))require __DIR__.'/languages/'.LANGUAGE.'.php';
if(Settings::Get('wb_maintainance_mode','__maintenance_missing__')==='__maintenance_missing__'){$error=Settings::Set('wb_maintainance_mode','0',false);if($error)throw new RuntimeException($MOD_MAINTAINANCE['INSTALL_FAILED']);}
