<?php
/**
 * WBCE CMS
 * Way Better Content Editing.
 * Visit https://wbce.org to learn more and to join the community.
 *
 * @copyright       WBCE Project (2015-)
 * @category        opffilter
 * @package         OPF Replace Contents
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



function opff_mod_opf_replace_stuff (&$sContent, $page_id, $section_id, $module, $wb) {
    if(is_string($sContent) && (!class_exists('Settings')
        || (Settings::Get('opf_replace_stuff', true) && ($page_id != 'backend'))
        || (Settings::Get('opf_replace_stuff_be', true) && ($page_id == 'backend')))){

        // Template does not want any replacement ?
        if (strpos($sContent,'<!--(NO REPLACE)-->') !== false) {return TRUE;}

        // Do we have any placeholders to move to ?
        if (strpos($sContent,'<!--(PH)') === false) {return TRUE;}

        // Do we have any stuff to move, if not abort?
        if (strpos($sContent,'<!--(REPLACE)') === false) {return TRUE;}

        // Does the stuf has at least one end, if not abort?
        if (strpos($sContent,'<!--(END)-->') === false) {return TRUE;}

        $pattern='/<!--\(REPLACE\)\s+([^\r\n]+?)\s*-->(.*?)<!--\(END\)-->/s';
        $matched=preg_match_all($pattern,$sContent,$matches,PREG_SET_ORDER);
        if($matched===false || $matched===0) return true;
        $processed=array();
        foreach($matches as $match){
            $source=$match[0];
            if(isset($processed[$source])) continue;
            $target=trim($match[1]);
            if($target==='') continue;
            $startMarker='<!--(PH) '.$target.'+ -->';
            $endMarker='<!--(PH) '.$target.'- -->';
            $start=strpos($sContent,$startMarker);
            if($start===false) continue;
            $rangeStart=$start+strlen($startMarker);
            $end=strpos($sContent,$endMarker,$rangeStart);
            if($end===false) continue;
            $processed[$source]=true;
            $sContent=str_replace($source,'',$sContent);
            $start=strpos($sContent,$startMarker);
            if($start===false) continue;
            $rangeStart=$start+strlen($startMarker);
            $end=strpos($sContent,$endMarker,$rangeStart);
            if($end===false) continue;
            $sContent=substr_replace($sContent,trim($match[2]),$rangeStart,$end-$rangeStart);
        }
    }

    return(TRUE);
}
