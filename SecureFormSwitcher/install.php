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
try{$secret=bin2hex(random_bytes(24));}catch(Throwable $error){throw new RuntimeException($SFS['RANDOM_FAILED']);}
$defaults=array(
    "wb_secform_secret"=>$secret,"wb_secform_secrettime"=>'86400',"wb_secform_timeout"=>'7200',
    "wb_session_timeout"=>'7200',"wb_secform_tokenname"=>'formtoken',"wb_secform_usefp"=>false,
    "fingerprint_with_ip_octets"=>"2"
);
foreach($defaults as $name=>$value){
    if(Settings::Get($name,'__sfs_missing__')!=='__sfs_missing__')continue;
    $error=Settings::Set($name,$value,false);
    if($error)throw new RuntimeException($SFS['SAVE_FAILED']);
}
