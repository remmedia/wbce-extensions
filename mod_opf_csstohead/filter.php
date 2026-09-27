<?php
/**
 * WBCE CMS
 * Way Better Content Editing.
 * Visit https://wbce.org to learn more and to join the community.
 *
 * @copyright       Ryan Djurovich (2004-2009)
 * @copyright       WebsiteBaker Org. e.V. (2009-2015)
 * @copyright       WBCE Project (2015-)
 * @category        opffilter
 * @package         OPF CSS to head
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


/**
 * moves all css definitions from <body> into <head> section
 */
function opff_mod_opf_csstohead (&$sContent, $page_id, $section_id, $module, $wb) {
    if(is_string($sContent) && (!class_exists('Settings')
        || (Settings::Get('opf_css_to_head', true) && ($page_id != 'backend'))
        || (Settings::Get('opf_css_to_head_be', false) && ($page_id == 'backend')))){
        $placeholder='<!--(PH) CSS HEAD BTM- -->';
        $placeholderPosition=strpos($sContent,$placeholder);
        $bodyPosition=stripos($sContent,'<body');
        if($placeholderPosition===false || $bodyPosition===false || $placeholderPosition>$bodyPosition) return true;

        $beforeBody=substr($sContent,0,$bodyPosition);
        $body=substr($sContent,$bodyPosition);
        $insert=array();
        $body=preg_replace_callback('/<style\b[^>]*>.*?<\/style\s*>|<link\b[^>]*>/is',function($match)use(&$insert){
            $tag=$match[0];
            if(strncasecmp(ltrim($tag),'<link',5)===0){
                $stylesheet=false;
                if(preg_match('/\brel\s*=\s*(?:(["\'])(.*?)\1|([^\s>]+))/i',$tag,$attribute)){
                    $value=isset($attribute[2])&&$attribute[2]!==''?$attribute[2]:(isset($attribute[3])?$attribute[3]:'');
                    $stylesheet=in_array('stylesheet',preg_split('/\s+/',strtolower(trim($value))),true);
                }
                $cssType=preg_match('/\btype\s*=\s*(?:(["\'])text\/css\1|text\/css(?:\s|>))/i',$tag)===1;
                if(!$stylesheet&&!$cssType)return $tag;
            }
            $insert[]=$tag;
            return '';
        },$body);
        if(!is_string($body) || !$insert) return true;
        $insert=array_values(array_unique($insert));
        $sContent=$beforeBody.$body;
        $replacement="\n".implode("\n",$insert)."\n".$placeholder;
        $sContent=substr_replace($sContent,$replacement,$placeholderPosition,strlen($placeholder));
    }
    return(TRUE);
}
