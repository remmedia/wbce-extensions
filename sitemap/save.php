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

// Include WB admin wrapper script
$admin_header = false;
$update_when_modified = true; // Tells script to update when this page was last updated
require(WB_PATH.'/modules/admin.php');
$asyncRequest = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
$asyncReply = static function ($ok, $message, $status = 200) use ($admin, $page_id) {
	$redirect = ADMIN_URL.'/pages/modify.php?page_id='.(int) $page_id;
	http_response_code((int) $status);
	header('Content-Type: application/json; charset=utf-8');
	header('Cache-Control: no-store');
	echo json_encode(array('ok'=>(bool) $ok,'message'=>(string) $message,'redirect'=>$redirect,'ftan'=>$admin->getFTAN()), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
	exit;
};

if ($admin->checkFTAN() == false) {
	if ($asyncRequest) $asyncReply(false, $MESSAGE['GENERIC_SECURITY_ACCESS'], 403);
	$admin->print_header();
	$admin->print_error($MESSAGE['GENERIC_SECURITY_ACCESS']);		
} 
if (!$asyncRequest) $admin->print_header();
//
// Update the mod_sitemap table with the contents
//
if(isset($_POST['header'])) {
	$header       = (string) $_POST['header'];
	$sitemaploop  = (string) ($_POST['sitemaploop'] ?? '');
	$footer       = (string) ($_POST['footer'] ?? '');
	$level_header = (string) ($_POST['level_header'] ?? '');
	$level_footer = (string) ($_POST['level_footer'] ?? '');
	
	$static = 0;
	if(isset($_POST['static']) AND $_POST['static'] == 'true') {
		$static = 1;
	} 
	$startatroot = isset($_POST['startatroot']) && (string) $_POST['startatroot'] === '1' ? 1 : 0;
	$show_hidden = isset($_POST['show_hidden']) && (string) $_POST['show_hidden'] === '1' ? 1 : 0;

	//
	// check the depth value	
	//
	$depth = max(0, (int) ($_POST['depth'] ?? 0));
	
	//
	// Work out what menus to use
	//
	$menus = "0";
	$post_menus   = $admin->get_post('menus');
	if($post_menus == ""){
		$post_menus = "0";
	}
	if(is_array($post_menus) && $post_menus){
		$menus = implode(",", array_map('intval', array_keys($post_menus)));
	}
	if(isset($_POST['all_menus']) && $_POST['all_menus'] == 0){
		$menus = "0";
	}
	
	$database->updateRow('{TP}mod_sitemap', 'section_id', array(
		'section_id'=>(int)$section_id, 'header'=>$header, 'sitemaploop'=>$sitemaploop,
		'footer'=>$footer, 'static'=>$static, 'level_header'=>$level_header,
		'level_footer'=>$level_footer, 'startatroot'=>$startatroot, 'depth'=>$depth,
		'menus'=>$menus, 'show_hidden'=>$show_hidden
	));
}

//
// Check if there is a database error, otherwise say successful
//
$goto = ADMIN_URL.'/pages/modify.php?page_id='.$page_id;
if($database->is_error()) {
	if ($asyncRequest) $asyncReply(false, $database->get_error(), 500);
	$admin->print_error($database->get_error(), $js_back);
} else {
	if ($asyncRequest) $asyncReply(true, $MESSAGE['PAGES_SAVED']);
	$admin->print_success($MESSAGE['PAGES_SAVED'], $goto);
}

// Print admin footer
$admin->print_footer();
