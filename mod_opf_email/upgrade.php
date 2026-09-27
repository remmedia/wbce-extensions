<?php
if (!defined('WB_PATH')) {
    if (!headers_sent()) header('Location: ../index.php', true, 301);
    exit;
}
if (!class_exists('Settings') || !is_readable(WB_PATH.'/modules/outputfilter_dashboard/functions.php')) return false;
require_once WB_PATH.'/modules/outputfilter_dashboard/functions.php';

$defaults = array(
    'opf_email_filter' => 1,
    'opf_mailto_filter' => 1,
    'opf_js_mailto' => 1,
    'opf_at_replacement' => '(at)',
    'opf_dot_replacement' => '(dot)',
);
foreach ($defaults as $name => $default) {
    if (Settings::Get($name, null) === null) Settings::Set($name, $default, false);
}

if (opf_is_registered('E-Mail')) opf_unregister_filter('E-Mail');
if (opf_is_registered('E-Mail Masking')) return true;

return opf_register_filter(array(
    'name' => 'E-Mail Masking',
    'type' => OPF_TYPE_PAGE,
    'file' => '{SYSVAR:WB_PATH}/modules/mod_opf_email/filter.php',
    'funcname' => 'opff_mod_opf_email',
    'desc' => array(
        'EN' => 'Protects email addresses in text and mail links against automated harvesting.',
        'DE' => 'Schützt E-Mail-Adressen in Texten und Mail-Links vor automatisiertem Auslesen.',
    ),
    'active' => (Settings::Get('opf_email_filter', 1) || Settings::Get('opf_mailto_filter', 1) || Settings::Get('opf_js_mailto', 1)) ? 1 : 0,
    'allowedit' => 0,
    'configurl' => ADMIN_URL.'/admintools/tool.php?tool=mod_opf_email',
    'pages_parent' => 'all,search',
));
