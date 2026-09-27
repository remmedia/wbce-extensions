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
 * @package         OPF Internal Link Replacer
 * @version         1.0.8
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



/*
 * replace all "[wblink{page_id}]" with real links
 */

function opff_mod_opf_wblink (&$content, $page_id, $section_id, $module, $wb) {
    if(is_string($content) && (!class_exists('Settings') || Settings::Get('opf_wblink', true))){
        global $database;

        $pattern = '/\[wblink([0-9]+)\]/i';
        $matched=preg_match_all($pattern, $content, $aMatches, PREG_SET_ORDER);
        if ($matched && is_object($database) && method_exists($database,'query'))
        {
            $aSearchReplaceList = array();
            foreach ($aMatches as $aMatch) {
                 // collect matches formatted like '[wblink123]' => 123
                $aSearchReplaceList[strtolower($aMatch[0])] = (int)$aMatch[1];
            }
            // build list of PageIds for SQL query
            $sPageIdList = implode(',', array_values(array_unique($aSearchReplaceList))); // '123,124,125'
            // replace all PageIds with '#' (stay on page death link)
            array_walk($aSearchReplaceList, function(&$value, $index){ $value = '#'; });
            $sql = 'SELECT `page_id`, `link` FROM `'.TABLE_PREFIX.'pages` '
                 . 'WHERE `page_id` IN('.$sPageIdList.')';
            $oPages=$database->query($sql);
            $queryOk=is_object($oPages)&&method_exists($oPages,'fetchRow')&&(!method_exists($oPages,'error')||(string)$oPages->error()==='');
            if ($queryOk) {
                while (($aPage = $oPages->fetchRow())) {
                    if(!is_array($aPage)||!isset($aPage['page_id']))continue;
                    $pageId=(int)$aPage['page_id'];
                    if (!empty($aPage['link'])) {
                        // Let WBCE build the URL so rewritten and index.php-free URLs work, too.
                        $url=is_object($wb)&&method_exists($wb,'page_link')?$wb->page_link((string)$aPage['link']):WB_URL.PAGES_DIRECTORY.(string)$aPage['link'].PAGE_EXTENSION;
                        if(!function_exists('wbce_apply_filters')){$bridge=WB_PATH.'/modules/wbce_hook_bridge/preinit.php';if(is_file($bridge))require_once $bridge;}
                        if(function_exists('wbce_apply_filters')) $url=wbce_apply_filters('page.url',$url,$pageId);
                        if(is_string($url)&&$url!=='') $aSearchReplaceList['[wblink'.$pageId.']']=$url;
                    }
                }
            }
            // replace all found [wblink**] tags with their urls
            $content = str_ireplace(
                array_keys($aSearchReplaceList),
                $aSearchReplaceList,
                $content
            );
        }
    }
    return(TRUE);
}
