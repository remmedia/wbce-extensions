<?php
if(!defined('WB_PATH')){
    if(!headers_sent()) header('Location: ../index.php',true,301);
    exit;
}
if(!class_exists('Settings')||!is_readable(WB_PATH.'/modules/outputfilter_dashboard/functions.php')) return false;
require_once WB_PATH.'/modules/outputfilter_dashboard/functions.php';
if(Settings::Get('opf_wblink',null)===null){$error=Settings::Set('opf_wblink',1,false);if($error)return false;}
if(opf_is_registered('Internal Link Replacer')&&opf_get_type('Internal Link Replacer',false)==OPF_TYPE_PAGE) return true;
if(opf_is_registered('Internal Link Replacer')) opf_unregister_filter('Internal Link Replacer');

$opfWblinkFilterDescription='Converts references such as [wblink12] into the current URL of the corresponding WBCE page.';
if(is_readable(__DIR__.'/languages/metadata/EN.php')) require __DIR__.'/languages/metadata/EN.php';
$descriptionEn=$opfWblinkFilterDescription;
if(is_readable(__DIR__.'/languages/metadata/DE.php')) require __DIR__.'/languages/metadata/DE.php';
$descriptionDe=$opfWblinkFilterDescription;
return opf_register_filter(array(
    'name'=>'Internal Link Replacer',
    'type'=>OPF_TYPE_PAGE,
    'file'=>'{SYSVAR:WB_PATH}/modules/mod_opf_wblink/filter.php',
    'funcname'=>'opff_mod_opf_wblink',
    'desc'=>array('EN'=>$descriptionEn,'DE'=>$descriptionDe),
    'active'=>Settings::Get('opf_wblink',1)?1:0,
    'allowedit'=>0,
    'pages_parent'=>'all,search',
));
