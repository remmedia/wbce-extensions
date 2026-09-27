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

require('../../config.php');

// suppress to print the header, so no new FTAN will be set
$admin_header = false;

// Tells script to update when this page was last updated
$update_when_modified = true;

// Include WB admin wrapper script
require(WB_PATH . '/modules/admin.php');
$asyncRequest = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
$asyncReply = static function ($ok, $message, $redirect, $status = 200) use ($admin) {
    http_response_code((int) $status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode(array('ok'=>(bool) $ok,'message'=>(string) $message,'redirect'=>(string) $redirect,'ftan'=>$admin->getFTAN()), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    exit;
};

if (!$admin->checkFTAN()) {
	if ($asyncRequest) $asyncReply(false, $MESSAGE['GENERIC_SECURITY_ACCESS'], ADMIN_URL . '/pages/modify.php?page_id=' . $page_id, 403);
    $admin->print_header();
    $admin->print_error($MESSAGE['GENERIC_SECURITY_ACCESS'], ADMIN_URL . '/pages/modify.php?page_id=' . $page_id);
}

// After check print the header
if (!$asyncRequest) $admin->print_header();

// Include the WB functions file
require_once(WB_PATH . '/framework/functions.php');

$sMediaUrl = WB_URL . MEDIA_DIRECTORY;
$bBackLink = isset($_POST['pagetree']);

// Update the mod_wysiwygs table with the contents
if (isset($_POST['content' . $section_id])) {
    $content   = $_POST['content' . $section_id];
    $searchfor = '@(<[^>]*=\s*")(' . preg_quote($sMediaUrl) . ')([^">]*".*>)@siU';
    $content   = preg_replace($searchfor, '$1{SYSVAR:MEDIA_REL}$3', $content);
    $text      = strip_tags($content);
    $sql       = 'UPDATE `' . TABLE_PREFIX . 'mod_wysiwyg` ' . 'SET `content`=\'' . $database->escapeString($content) . '\', ' . '`text`=\'' . $database->escapeString($text) . '\' ' . 'WHERE `section_id`=' . (int) $section_id;
    $database->query($sql);
}

$sec_anchor = (defined('SEC_ANCHOR') && (SEC_ANCHOR != '') ? '#' . SEC_ANCHOR . $section['section_id'] : '');
if (defined('EDIT_ONE_SECTION') && EDIT_ONE_SECTION) {
    $edit_page = ADMIN_URL . '/pages/modify.php?page_id=' . $page_id . '&wysiwyg=' . $section_id;
} elseif ($bBackLink) {
    $edit_page = ADMIN_URL . '/pages/index.php';
} else {
    $edit_page = ADMIN_URL . '/pages/modify.php?page_id=' . $page_id . $sec_anchor;
}

// Check if there is a database error, otherwise say successful
if ($database->is_error()) {
	if ($asyncRequest) $asyncReply(false, $database->get_error(), $edit_page, 500);
    $admin->print_error($database->get_error(), $js_back);
} else {
	if ($asyncRequest) $asyncReply(true, $MESSAGE['PAGES_SAVED'], $edit_page);
    $admin->print_success($MESSAGE['PAGES_SAVED'], $edit_page);
}

// Print admin footer
$admin->print_footer();
