<?php
if(!defined('WB_PATH')){
    if(!headers_sent()) header('Location: ../index.php',true,301);
    exit;
}
if(!class_exists('Settings')||!is_readable(WB_PATH.'/modules/outputfilter_dashboard/functions.php')) return false;
require_once WB_PATH.'/modules/outputfilter_dashboard/functions.php';
foreach(array('opf_move_stuff'=>1,'opf_move_stuff_be'=>1) as $name=>$default){
    if(Settings::Get($name,null)===null&&Settings::Set($name,$default,false)) return false;
}
if(opf_is_registered('Move Contents')) return true;

$opfMoveStuffFilterDescription='Moves content enclosed by MOVE markers to the corresponding placeholders without discarding unmatched content.';
if(is_readable(__DIR__.'/languages/metadata/EN.php')) require __DIR__.'/languages/metadata/EN.php';
$descriptionEn=$opfMoveStuffFilterDescription;
if(is_readable(__DIR__.'/languages/metadata/DE.php')) require __DIR__.'/languages/metadata/DE.php';
$descriptionDe=$opfMoveStuffFilterDescription;
$registered=opf_register_filter(array(
    'name'=>'Move Contents',
    'type'=>OPF_TYPE_PAGE_LAST,
    'file'=>'{SYSVAR:WB_PATH}/modules/mod_opf_move_stuff/filter.php',
    'funcname'=>'opff_mod_opf_move_stuff',
    'desc'=>array('EN'=>$descriptionEn,'DE'=>$descriptionDe),
    'active'=>(Settings::Get('opf_move_stuff',1)||Settings::Get('opf_move_stuff_be',1))?1:0,
    'allowedit'=>0,
    'pages_parent'=>'all,backend,search',
));
if($registered&&function_exists('opf_move_up_before')) opf_move_up_before('Move Contents',array('Replace Contents','Cache Control'));
return (bool)$registered;
