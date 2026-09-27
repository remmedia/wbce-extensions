<?php
/**
 *
 * @category        modules
 * @package         wrapper
 * @author          WebsiteBaker Project
 * @copyright       2009-2011, Website Baker Org. e.V.
 * @link			http://www.websitebaker2.org/
 * @license         http://www.gnu.org/licenses/gpl.html
 * @platform        WebsiteBaker 2.8.x
 * @requirements    PHP 5.2.2 and higher
 * @version      	$Id: save.php 1538 2011-12-10 15:06:15Z Luisehahne $
 * @filesource		$HeadURL: http://svn.websitebaker2.org/branches/2.8.x/wb/modules/wrapper/install.php $
 * @lastmodified    $Date: 2011-01-10 13:21:47 +0100 (Mo, 10 Jan 2011) $
 *
 */

require('../../config.php');

$admin_header = false;
// Tells script to update when this page was last updated
$update_when_modified = true;
// Include WB admin wrapper script
require(WB_PATH.'/modules/admin.php');
$js_back = ADMIN_URL.'/pages/modify.php?page_id='.$page_id;
$asyncRequest = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
$asyncReply = static function ($ok, $message, $status = 200) use ($admin, $js_back) {
	http_response_code((int) $status);
	header('Content-Type: application/json; charset=utf-8');
	header('Cache-Control: no-store');
	echo json_encode(array('ok'=>(bool) $ok,'message'=>(string) $message,'redirect'=>$js_back,'ftan'=>$admin->getFTAN()), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
	exit;
};
if (!$admin->checkFTAN())
{
	if ($asyncRequest) $asyncReply(false, $MESSAGE['GENERIC_SECURITY_ACCESS'], 403);
	$admin->print_header();
	$admin->print_error($MESSAGE['GENERIC_SECURITY_ACCESS'], ADMIN_URL.'/pages/modify.php?page_id='.$page_id);
}
if (!$asyncRequest) $admin->print_header();

// Update the mod_wrapper table with the contents
if(isset($_POST['url'])) {
	$rawUrl = trim(str_replace(array("\r", "\n"), '', strip_tags((string) $_POST['url'])));
	$testUrl = str_replace('[WB_URL]', WB_URL, $rawUrl);
	$urlScheme = parse_url($testUrl, PHP_URL_SCHEME);
	if ($urlScheme !== null && $urlScheme !== false && !in_array(strtolower($urlScheme), array('http', 'https'), true)) {
		if ($asyncRequest) $asyncReply(false, isset($MOD_WRAPPER['INVALID_URL']) ? $MOD_WRAPPER['INVALID_URL'] : $MESSAGE['GENERIC_INVALID'], 422);
		$admin->print_error(isset($MOD_WRAPPER['INVALID_URL']) ? $MOD_WRAPPER['INVALID_URL'] : $MESSAGE['GENERIC_INVALID'], $js_back);
	}
	$height = max(1, min(10000, (int) ($_POST['height'] ?? 400)));
	$database->updateRow('{TP}mod_wrapper', 'section_id', array('section_id'=>(int)$section_id, 'url'=>$rawUrl, 'height'=>$height));
}

// Check if there is a database error, otherwise say successful
if($database->is_error()) {
	if ($asyncRequest) $asyncReply(false, $database->get_error(), 500);
	$admin->print_error($database->get_error(), $js_back);
} else {
	if ($asyncRequest) $asyncReply(true, $MESSAGE['PAGES_SAVED']);
	$admin->print_success($MESSAGE['PAGES_SAVED'], ADMIN_URL.'/pages/modify.php?page_id='.$page_id);
}

// Print admin footer
$admin->print_footer();
