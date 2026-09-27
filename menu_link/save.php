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

require_once '../../config.php';

$admin_header         = false; // don't print header immediately
$update_when_modified = true; // Tell script to update when this page was last updated
require WB_PATH.'/modules/admin.php'; // Include WB admin wrapper script
$js_back = ADMIN_URL.'/pages/modify.php?page_id='.$page_id;
$asyncRequest = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
$asyncReply = static function ($ok, $message, $status = 200) use ($admin, $js_back) {
    http_response_code((int) $status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode(array('ok' => (bool) $ok, 'message' => (string) $message, 'redirect' => $js_back, 'ftan' => $admin->getFTAN()), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    exit;
};

if (!$admin->checkFTAN()) {
	if ($asyncRequest) $asyncReply(false, $MESSAGE['GENERIC_SECURITY_ACCESS'], 403);
    $admin->print_header();
    $admin->print_error($MESSAGE['GENERIC_SECURITY_ACCESS'], $js_back);
}

if (!$asyncRequest) $admin->print_header();

if (isset($_POST['menu_link'])) {
    $linkType = (string) $admin->get_post('linktype');
    $linkType = in_array($linkType, array('ext', 'int'), true) ? $linkType : 'int';
    $redirectType = (string) $admin->get_post('r_type');
    $redirectType = in_array($redirectType, array('200', '301', '302'), true) ? $redirectType : '302';
    $target = (string) $admin->get_post('target');
    $target = in_array($target, array('_blank', '_self', '_top'), true) ? $target : '_self';
    $external = $linkType === 'ext' ? trim((string) $admin->get_post('extern')) : '';
    $external = str_replace(array("\r", "\n"), '', $external);
    $aUpdatePageTable = array(
        'page_id'  => $page_id,
        'target'   => $admin->add_slashes($target),
    );
    $database->updateRow('{TP}pages', 'page_id', $aUpdatePageTable);
    
    $aUpdateModTable = array(
        'page_id'        => $page_id,
        'target_page_id' => $linkType === 'ext' ? -1 : max(0, (int) $admin->get_post('menu_link')),
        'redirect_type'  => $redirectType,
        'anchor'         => $admin->add_slashes($admin->get_post('anchor')),
        'extern'         => $admin->add_slashes($external),
    );
    $database->updateRow('{TP}mod_menu_link', 'page_id', $aUpdateModTable);

    // Check if there is a database error, otherwise say successful
    if ($database->is_error()) {
		if ($asyncRequest) $asyncReply(false, $database->get_error(), 500);
        $admin->print_error($database->get_error(), $js_back);
    } else {
		if ($asyncRequest) $asyncReply(true, $MESSAGE['PAGES_SAVED']);
        $admin->print_success($MESSAGE['PAGES_SAVED'], $js_back);
    }
} else {
	if ($asyncRequest) $asyncReply(false, $MESSAGE['GENERIC_FILL_IN_ALL'], 422);
    $admin->print_error($MESSAGE['GENERIC_FILL_IN_ALL'], $js_back);
}
$admin->print_footer();
