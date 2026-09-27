<?php
/**
 * WBCE CMS
 * Way Better Content Editing.
 * Visit https://wbce.org to learn more and to join the community.
 *
 * @copyright       WBCE Project (2015-)
 * @category        opffilter
 * @package         OPF Move Contents
 * @version         1.0.9
 * @authors         Martin Hecht (mrbaseman)
 * @link            https://forum.wbce.org/viewtopic.php?id=176
 * @license         GNU GPL2 (or any later version)
 * @platform        WBCE 1.7.0 (compatible with 1.6.8)
 * @requirements    OutputFilter Dashboard 1.6.15 and PHP 8.2 or higher
 *
 **/


/* -------------------------------------------------------- */
// Must include code to stop this file being accessed directly
if(!defined('WB_PATH')) {
        // Stop this file being access directly
        if(!headers_sent()) header("Location: ../index.php",TRUE,301);
        die('<head><title>Access denied</title></head><body><h2 style="color:red;margin:3em auto;text-align:center;">Cannot access this file directly</h2></body></html>');
}
/* -------------------------------------------------------- */


if(!class_exists('Settings')) return false;
$opfSettingNames=array('opf_move_stuff','opf_move_stuff_be');
$opfDelete=$database->query("DELETE FROM `{TP}settings` WHERE `name` IN ('opf_move_stuff','opf_move_stuff_be')");
if($opfDelete===false||$database->is_error())return false;
foreach($opfSettingNames as $opfSettingName)unset(Settings::$aSettings[$opfSettingName]);

// check whether outputfilter-module is installed {
if(file_exists(WB_PATH.'/modules/outputfilter_dashboard/functions.php')) {
  require_once(WB_PATH.'/modules/outputfilter_dashboard/functions.php');
  // un-install filter
  if(opf_is_registered('Move Contents')) opf_unregister_filter('Move Contents');
}
return true;
