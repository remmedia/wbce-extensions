<?php
/**
 * WBCE CMS
 * Way Better Content Editing.
 * Visit https://wbce.org to learn more and to join the community.
 *
 * @copyright       WBCE Project (2015-2019)
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



function opff_mod_opf_move_stuff (&$sContent, $page_id, $section_id, $module, $wb) {
    if(is_string($sContent) && (!class_exists('Settings')
        || (Settings::Get('opf_move_stuff', true) && ($page_id != 'backend'))
        || (Settings::Get('opf_move_stuff_be', true) && ($page_id == 'backend')))){

        // Templates does not want any movement ?
        if (strpos($sContent,'<!--(NO MOVE)-->') !== false) {return TRUE;}

        // Do we have any placeholders to move to ?
        if (strpos($sContent,'<!--(PH)') === false) {return TRUE;}

        // Do we have any stuff to move, if not abort?
        if (strpos($sContent,'<!--(MOVE)') === false) {return TRUE;}

        // Does the stuf has at least one end, if not abort?
        if (strpos($sContent,'<!--(END)-->') === false) {return TRUE;}


        $pattern='/<!--\(MOVE\)\s+([^\r\n]*?([+-]))\s*-->(.*?)<!--\(END\)-->/s';
        $matched=preg_match_all($pattern,$sContent,$matches,PREG_SET_ORDER);
        if($matched===false || $matched===0) return true;
        $processed=array();
        foreach($matches as $match){
            $source=$match[0];
            if(isset($processed[$source])) continue;
            $target=trim($match[1]);
            $direction=$match[2];
            $placeholder='<!--(PH) '.$target.' -->';
            $position=strpos($sContent,$placeholder);
            if($position===false) continue;
            $processed[$source]=true;
            $sContent=str_replace($source,'',$sContent);
            $position=strpos($sContent,$placeholder);
            if($position===false) continue;
            $moved=trim($match[3]);
            $replacement=$direction==='+'?$placeholder."\n".$moved:$moved."\n".$placeholder;
            $sContent=substr_replace($sContent,$replacement,$position,strlen($placeholder));
        }

    }
    return(TRUE);
}
