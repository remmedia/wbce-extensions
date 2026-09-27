<?php
/**
 *
 * @category        modules
 * @package         miniform
 * @author          Ruud Eisinga / Dev4me
 * @link			http://www.dev4me.nl/modules-snippets/opensource/miniform/
 * @license         http://www.gnu.org/licenses/gpl.html
 * @platform        WebsiteBaker 2.8.x
 * @requirements    PHP 5.6 and higher
 * @version         0.15.0
 * @lastmodified    April 30, 2019
 *
 */


if(defined('WB_PATH') == false) { exit("Cannot access this file directly"); }
$MF=array();
require __DIR__.'/languages/EN.php';
$miniformInstallLanguage=defined('LANGUAGE')?preg_replace('/[^A-Z]/','',strtoupper((string)LANGUAGE)):'EN';
if($miniformInstallLanguage!=='EN'&&is_file(__DIR__.'/languages/'.$miniformInstallLanguage.'.php'))require __DIR__.'/languages/'.$miniformInstallLanguage.'.php';

// Create tables
$mod_miniform = 'CREATE TABLE IF NOT EXISTS `'.TABLE_PREFIX.'mod_miniform` ('
	. ' `section_id` INT NOT NULL DEFAULT \'0\','
	. ' `email` VARCHAR(128) NOT NULL DEFAULT \'\',' 
	. ' `emailfrom` VARCHAR(128) NOT NULL DEFAULT \'\',' 
	. ' `subject` VARCHAR(255) NOT NULL DEFAULT \'\',' 
	. ' `confirm_user` INT NOT NULL DEFAULT \'0\',' 
	. ' `confirm_subject` VARCHAR(255) NOT NULL DEFAULT \'\',' 
	. ' `template` VARCHAR(64) NOT NULL DEFAULT \'form\',' 
	. ' `successpage` INT NOT NULL DEFAULT \'0\',' 
	. ' `use_ajax` INT NOT NULL DEFAULT \'1\',' 
	. ' `use_recaptcha` INT NOT NULL DEFAULT \'0\',' 
	. ' `recaptcha_key` VARCHAR(64) NOT NULL DEFAULT \'\',' 
	. ' `recaptcha_secret` VARCHAR(64) NOT NULL DEFAULT \'\',' 
	. ' `remote_id` VARCHAR(64) NOT NULL DEFAULT \'\',' 
	. ' `remote_name` VARCHAR(64) NOT NULL DEFAULT \'\',' 
	. ' `disable_tls` INT NOT NULL DEFAULT \'0\',' 
	. ' `no_store` INT NOT NULL DEFAULT \'0\','
	. ' `upload_limit_mb` INT UNSIGNED NOT NULL DEFAULT \'512\','
	. ' PRIMARY KEY ( `section_id` ) '
	. ' )'
	. 'COLLATE=\'utf8_unicode_ci\''
	. 'ENGINE=InnoDB';
if($database->query($mod_miniform)===false)throw new RuntimeException($MF['INSTALL_DATABASE_FAILED']);

$mod_miniformdata = 'CREATE TABLE IF NOT EXISTS `'.TABLE_PREFIX.'mod_miniform_data` ('
	. ' `message_id` INT NOT NULL auto_increment,'
	. ' `section_id` INT NOT NULL DEFAULT \'0\','
	. ' `user_id` INT NOT NULL DEFAULT \'0\','
	. ' `data` MEDIUMTEXT NOT NULL,' 
	. ' `guid` VARCHAR(64) NOT NULL DEFAULT \'\','
	. ' `session_data` MEDIUMTEXT NOT NULL,' 
	. ' `submitted_when` INT NOT NULL DEFAULT \'0\',' 
	. ' PRIMARY KEY ( `message_id` ) '
	. ' )'
	. 'COLLATE=\'utf8_unicode_ci\''
	. 'ENGINE=InnoDB';
if($database->query($mod_miniformdata)===false)throw new RuntimeException($MF['INSTALL_DATABASE_FAILED']);


$path = WB_PATH.'/modules/miniform/';
if(file_exists($path.'new_frontend.css')) {
	if(!rename($path.'new_frontend.css',$path.'frontend.css')) {
		throw new RuntimeException($MF['INSTALL_FILE_FAILED']);
	}
}


$defaultsPath=$path.'defaults/';
if(is_dir($defaultsPath)){
	$files=scandir($defaultsPath);
	if($files===false)throw new RuntimeException($MF['INSTALL_FILE_FAILED']);
	foreach($files as $file){
		if($file!=='.'&&$file!=='..'&&!rename($defaultsPath.$file,$path.'templates/'.$file)){
			throw new RuntimeException($MF['INSTALL_FILE_FAILED']);
		}
	}
}
					
					



?>
