<?php
if(!defined('WB_PATH')){
    if(!headers_sent()) header('Location: ../index.php',true,301);
    exit;
}
if(!class_exists('Settings')||!is_readable(WB_PATH.'/modules/outputfilter_dashboard/functions.php')) return false;
require_once WB_PATH.'/modules/outputfilter_dashboard/functions.php';
foreach(array('opf_remove_system_ph'=>1,'opf_remove_system_ph_be'=>1) as $name=>$default){
    if(Settings::Get($name,null)===null&&Settings::Set($name,$default,false)) return false;
}
if(opf_is_registered('Remove System PH')&&opf_get_type('Remove System PH',false)==OPF_TYPE_PAGE_FINAL) return true;
if(opf_is_registered('Remove System PH')) opf_unregister_filter('Remove System PH');

$opfRemovePhFilterDescription='Removes unused WBCE placeholder comments from the final page output.';
if(is_readable(__DIR__.'/languages/metadata/EN.php')) require __DIR__.'/languages/metadata/EN.php';
$descriptionEn=$opfRemovePhFilterDescription;
if(is_readable(__DIR__.'/languages/metadata/DE.php')) require __DIR__.'/languages/metadata/DE.php';
$descriptionDe=$opfRemovePhFilterDescription;
return opf_register_filter(array(
    'name'=>'Remove System PH',
    'type'=>OPF_TYPE_PAGE_FINAL,
    'file'=>'{SYSVAR:WB_PATH}/modules/mod_opf_remove_system_ph/filter.php',
    'funcname'=>'opff_mod_opf_remove_system_ph',
    'desc'=>array('EN'=>$descriptionEn,'DE'=>$descriptionDe),
    'active'=>(Settings::Get('opf_remove_system_ph',1)||Settings::Get('opf_remove_system_ph_be',1))?1:0,
    'allowedit'=>0,
    'pages_parent'=>'all,backend,search',
));
