<?php
if (!defined('WB_PATH')) {
    if (!headers_sent()) header('Location: ../index.php', true, 301);
    exit;
}
if (!class_exists('Settings') || !is_readable(WB_PATH.'/modules/outputfilter_dashboard/functions.php')) return false;
require_once WB_PATH.'/modules/outputfilter_dashboard/functions.php';
foreach (array('opf_css_to_head'=>1,'opf_css_to_head_be'=>0) as $name=>$default) {
    if (Settings::Get($name, null) === null && Settings::Set($name,$default,false)) return false;
}
if (opf_is_registered('CSS to head')) return true;

$opfCssToHeadFilterDescription='Moves stylesheet links and CSS blocks from the page body into the document head.';
if(is_readable(__DIR__.'/languages/metadata/EN.php')) require __DIR__.'/languages/metadata/EN.php';
$descriptionEn=$opfCssToHeadFilterDescription;
if(is_readable(__DIR__.'/languages/metadata/DE.php')) require __DIR__.'/languages/metadata/DE.php';
$descriptionDe=$opfCssToHeadFilterDescription;
$registered=opf_register_filter(array(
    'name'=>'CSS to head',
    'type'=>OPF_TYPE_PAGE,
    'file'=>'{SYSVAR:WB_PATH}/modules/mod_opf_csstohead/filter.php',
    'funcname'=>'opff_mod_opf_csstohead',
    'desc'=>array('EN'=>$descriptionEn,'DE'=>$descriptionDe),
    'active'=>Settings::Get('opf_css_to_head',1)?1:0,
    'allowedit'=>0,
    'pages_parent'=>'all,search',
));
if($registered && function_exists('opf_move_up_before')) opf_move_up_before('CSS to head',array('E-Mail Masking','E-Mail','WB-Link'));
return (bool)$registered;
