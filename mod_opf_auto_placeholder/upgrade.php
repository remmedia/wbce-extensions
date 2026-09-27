<?php
if (!defined('WB_PATH')) {
    if (!headers_sent()) header('Location: ../index.php', true, 301);
    exit;
}
if (!class_exists('Settings') || !is_readable(WB_PATH.'/modules/outputfilter_dashboard/functions.php')) return false;
require_once WB_PATH.'/modules/outputfilter_dashboard/functions.php';

foreach (array('opf_auto_placeholder'=>1, 'opf_auto_placeholder_be'=>1) as $name=>$default) {
    if (Settings::Get($name, null) === null && Settings::Set($name,$default,false)) return false;
}

if (opf_is_registered('Auto Placeholder') && opf_get_type('Auto Placeholder', false) == OPF_TYPE_PAGE_FIRST) return true;
if (opf_is_registered('Auto Placeholder')) opf_unregister_filter('Auto Placeholder');

$opfAutoPlaceholderFilterDescription='Adds placeholders (hooks) that output filters can use for safe replacements and insertions.';
if (is_readable(__DIR__.'/languages/metadata/EN.php')) require __DIR__.'/languages/metadata/EN.php';
$descriptionEn=$opfAutoPlaceholderFilterDescription;
if (is_readable(__DIR__.'/languages/metadata/DE.php')) require __DIR__.'/languages/metadata/DE.php';
$descriptionDe=$opfAutoPlaceholderFilterDescription;

return opf_register_filter(array(
    'name'=>'Auto Placeholder',
    'type'=>OPF_TYPE_PAGE_FIRST,
    'file'=>'{SYSVAR:WB_PATH}/modules/mod_opf_auto_placeholder/filter.php',
    'funcname'=>'opff_mod_opf_auto_placeholder',
    'desc'=>array('EN'=>$descriptionEn,'DE'=>$descriptionDe),
    'active'=>Settings::Get('opf_auto_placeholder',1)?1:0,
    'allowedit'=>0,
    'pages_parent'=>'all,backend,search',
));
