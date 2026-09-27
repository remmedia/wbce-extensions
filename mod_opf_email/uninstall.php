<?php
/**
 * WBCE CMS
 * Way Better Content Editing.
 * Visit https://wbce.org to learn more and to join the community.
 *
 * @copyright    Ryan Djurovich (2004-2009)
 * @copyright    WebsiteBaker Org. e.V. (2009-2015)
 * @copyright    WBCE Project (2015-)
 * @category     tool
 * @package      OPF E-Mail
 * @version      1.1.8
 * @authors      Martin Hecht (mrbaseman)
 * @link         https://forum.wbce.org/viewtopic.php?id=176
 * @license      GNU GPL2 (or any later version)
 * @platform     WBCE 1.x
 * @requirements OutputFilter Dashboard 1.5.x and PHP 5.4 or higher
 *
 **/

/* -------------------------------------------------------- */
// Must include code to stop this file being accessed directly
if (!defined('WB_PATH')) {
    // Stop this file being access directly
    if (!headers_sent()) {
        header("Location: ../index.php", true, 301);
    }
    die('<head><title>Access denied</title></head><body><h2 style="color:red;margin:3em auto;text-align:center;">Cannot access this file directly</h2></body></html>');
}
/* -------------------------------------------------------- */

if (!class_exists('Settings')) return false;
$opfSettingNames=array('opf_email','opf_email_filter','opf_mailto_filter','opf_js_mailto','opf_at_replacement','opf_dot_replacement');
$opfDelete=$database->query("DELETE FROM `{TP}settings` WHERE `name` IN ('opf_email','opf_email_filter','opf_mailto_filter','opf_js_mailto','opf_at_replacement','opf_dot_replacement')");
if($opfDelete===false||$database->is_error())return false;
foreach($opfSettingNames as $opfSettingName)unset(Settings::$aSettings[$opfSettingName]);

// check whether outputfilter-module is installed {
if (file_exists(WB_PATH.'/modules/outputfilter_dashboard/functions.php')) {
    require_once(WB_PATH.'/modules/outputfilter_dashboard/functions.php');
    // un-install filter
    if (opf_is_registered('E-Mail Masking')) {
        opf_unregister_filter('E-Mail Masking');
    }
    if (opf_is_registered('E-Mail')) {
        opf_unregister_filter('E-Mail');
    }
}
