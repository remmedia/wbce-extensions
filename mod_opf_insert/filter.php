<?php
/**
 * WBCE CMS
 * Way Better Content Editing.
 * Visit https://wbce.org to learn more and to join the community.
 *
 * @copyright       WBCE Project (2015-)
 * @category        opffilter
 * @package         OPF Insert
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



function opff_mod_opf_insert (&$content, $page_id, $section_id, $module, $wb) {
    if(is_string($content) && class_exists('I') && method_exists('I','doFilter') && (!class_exists('Settings')
        || (Settings::Get('opf_insert', true) && ($page_id != 'backend'))
        || (Settings::Get('opf_insert_be', true) && ($page_id == 'backend')))){
        $filtered=I::doFilter($content);
        if(is_string($filtered)) $content=$filtered;
    }
    return(TRUE);
}
