<?php
/**
 * WBCE CMS
 * Way Better Content Editing.
 * Visit https://wbce.org to learn more and to join the community.
 *
 * @copyright Ryan Djurovich (2004-2009)
 * @copyright WebsiteBaker Org. e.V. (2009-2015)
 * @copyright WBCE Project (2015-)
 * @license GNU GPL2 (or any later version)
 */

// Must include code to stop this file being access directly
defined('WB_PATH') or die("Cannot access this file directly");
require_once __DIR__.'/compat.php';

// get target_page_id
$sSql  = 'SELECT * FROM `{TP}mod_menu_link` WHERE `page_id` = '.(int)PAGE_ID;
$rQueryPageData = $database->query($sSql);

if ($rQueryPageData->numRows() == 1) {
    $aPageData = $rQueryPageData->fetchRow(MYSQLI_ASSOC); // generate assoc array from query

    if ($aPageData['redirect_type'] == 301) {
        @header('HTTP/1.1 301 Moved Permanently');	// 301 redirect
    }

    if ($aPageData['target_page_id'] == "-1") {
        if ($aPageData['extern'] != '') {
            $sTargetUrl = $aPageData['extern'];
            if (strpos($sTargetUrl, '[WB_URL]') !== false) {
                $sTargetUrl = str_replace('[WB_URL]', WB_URL, $sTargetUrl);
            }

            // convert [wblinkXX] into proper URLs
            if (strpos($sTargetUrl, '[wblink') !== false) {
                $iTargetPageID = sitemap_getBetween($sTargetUrl, '[wblink', ']');
                $sAnchor = '';

                // allow for manual anchors like: [wblink777]#myManualAnchor
                if (strpos($sTargetUrl, '#') !== false) {
                    $sAnchor = substr($sTargetUrl, strpos($sTargetUrl, "#"));
                }
                $sTargetUrl = $wb->page_link($iTargetPageID).$sAnchor;
            }
            // Prevent response-splitting and unsafe URI schemes while keeping
            // absolute HTTP(S), root-relative and WBCE-generated links valid.
            $sTargetUrl = str_replace(array("\r", "\n"), '', trim($sTargetUrl));
            $sScheme = parse_url($sTargetUrl, PHP_URL_SCHEME);
            if ($sScheme === null || $sScheme === false || in_array(strtolower($sScheme), array('http', 'https'), true)) {
                header('Location: '.$sTargetUrl);
                exit;
            }
        }
    } else {
        if ($aPageData['redirect_type'] == 200) {
            global $wb;
            global $page_id;
            $page_id = intval($aPageData['target_page_id']);
            $wb->page_id = $page_id;
            page_content(1);
        // we keep the updated page_id for the second block
        } else {
            // generate anchor if anchor is set
            $sAnchor = '';
            if ($aPageData['anchor'] != '0') {
                $iSectionID = (int) filter_var($aPageData['anchor'], FILTER_SANITIZE_NUMBER_INT);
                $sAnchor = '#'.SEC_ANCHOR.$iSectionID;
            }

            // get link of target-page
            $sSql  = 'SELECT `link` FROM `{TP}pages` WHERE `page_id` = '.$aPageData['target_page_id'];
            if ($sTargetPageLink = menu_link_db_value($database, $sSql)) {
                $sTargetUrl = WB_URL.PAGES_DIRECTORY.$sTargetPageLink.PAGE_EXTENSION.$sAnchor;
                header('Location: '.$sTargetUrl);
                exit;
            }
        }
    }
}

function sitemap_getBetween($sContent, $sStart, $sEnd)
{
    $arr = explode($sStart, $sContent);
    if (isset($arr[1])) {
        $arr = explode($sEnd, $arr[1]);
        return $arr[0];
    }
    return '';
}
