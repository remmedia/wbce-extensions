<?php
/**
 * WBCE CMS
 * Way Better Content Editing.
 * Visit https://wbce.org to learn more and to join the community.
 *
 * @copyright       WBCE Project (2015-)
 * @category        opffilter
 * @package         OPF Auto Placeholder
 * @version         1.3.4
 * @authors         Martin Hecht (mrbaseman)
 * @link            https://forum.wbce.org/viewtopic.php?id=176
 * @license         GNU GPL2 (or any later version)
 * @platform        WBCE 1.4.x
 * @requirements    OutputFilter Dashboard 1.5.x and PHP 5.4 or higher
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


if (!class_exists('Settings')) return false;
$opfSettingNames=array('opf_auto_placeholder','opf_auto_placeholder_be');
$opfDelete=$database->query("DELETE FROM `{TP}settings` WHERE `name` IN ('opf_auto_placeholder','opf_auto_placeholder_be')");
if($opfDelete===false||$database->is_error())return false;
foreach($opfSettingNames as $opfSettingName)unset(Settings::$aSettings[$opfSettingName]);

// check whether outputfilter-module is installed {
if(file_exists(WB_PATH.'/modules/outputfilter_dashboard/functions.php')) {
  require_once(WB_PATH.'/modules/outputfilter_dashboard/functions.php');
  // un-install filter
  if (opf_is_registered('Auto Placeholder')) opf_unregister_filter('Auto Placeholder');
}

return true;
