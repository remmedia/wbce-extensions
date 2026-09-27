<?php
if(!defined('WB_PATH')){
    if(!headers_sent()) header('Location: ../index.php',true,301);
    exit;
}
if(!class_exists('Settings')||!is_readable(WB_PATH.'/modules/outputfilter_dashboard/functions.php')) return false;
require_once WB_PATH.'/modules/outputfilter_dashboard/functions.php';
foreach(array('opf_insert'=>1,'opf_insert_be'=>1) as $name=>$default){
    if(Settings::Get($name,null)===null&&Settings::Set($name,$default,false)) return false;
}
if(opf_is_registered('Insert')) opf_unregister_filter('Insert');
if(opf_is_registered('Class Insert Helper')) return true;

$opfInsertFilterDescription='Processes the placeholders supplied by WBCE Insert at the end of page generation.';
if(is_readable(__DIR__.'/languages/metadata/EN.php')) require __DIR__.'/languages/metadata/EN.php';
$descriptionEn=$opfInsertFilterDescription;
if(is_readable(__DIR__.'/languages/metadata/DE.php')) require __DIR__.'/languages/metadata/DE.php';
$descriptionDe=$opfInsertFilterDescription;
return opf_register_filter(array(
    'name'=>'Class Insert Helper',
    'type'=>OPF_TYPE_PAGE_LAST,
    'file'=>'{SYSVAR:WB_PATH}/modules/mod_opf_insert/filter.php',
    'funcname'=>'opff_mod_opf_insert',
    'desc'=>array('EN'=>$descriptionEn,'DE'=>$descriptionDe),
    'active'=>(Settings::Get('opf_insert',1)||Settings::Get('opf_insert_be',1))?1:0,
    'allowedit'=>0,
    'pages_parent'=>'all,backend,search',
));
