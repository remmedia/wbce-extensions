<?php
/**
 *
 * @category        modules
 * @package         miniform
 * @author          Ruud Eisinga / Dev4me
 * @link			http://www.dev4me.nl/modules-snippets/opensource/miniform/
 * @license         http://www.gnu.org/licenses/gpl.html
 * @platform        WebsiteBaker 2.8.x
 * @requirements    PHP 5.6 and higher
 * @version         0.15.0
 * @lastmodified    April 30, 2019
 *
 */

require_once('../../config.php');
require_once (WB_PATH.'/framework/functions.php');
$update_when_modified = true; 
$miniformAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
if ($miniformAjax) {
	$admin_header = false;
}
require(WB_PATH.'/modules/admin.php');

$sLangPath = __DIR__ . '/languages';
require_once $sLangPath . '/EN.php';
if (defined('LANGUAGE') && LANGUAGE !== 'EN' && is_file($sLangPath . '/' . LANGUAGE . '.php')) {
	require_once $sLangPath . '/' . LANGUAGE . '.php';
}
$miniformScalar=static function($key,$default=''){
	if(!array_key_exists($key,$_POST))return $default;
	$value=$_POST[$key];
	return is_scalar($value)||$value===null?$value:$default;
};

if (!$admin->checkFTAN()) {
	if ($miniformAjax) {
		header('Content-Type: application/json; charset=UTF-8');
		header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
		http_response_code(403);
		echo json_encode(array('success' => false, 'message' => $MESSAGE['GENERIC_SECURITY_ACCESS'] ?? $MF['SAVE_FAILED'], 'ftan' => $admin->getFTAN()));
		exit;
	}
	$admin->print_error($MESSAGE['GENERIC_SECURITY_ACCESS'] ?? $MF['SAVE_FAILED'], $js_back);
}

if(array_key_exists('section_id',$_POST)) {
	$section_id = (int)$miniformScalar('section_id',0);
	$email = $admin->add_slashes(strip_tags((string)$miniformScalar('email','')));
	$emailfrom = $admin->add_slashes(strip_tags((string)$miniformScalar('emailfrom','')));
	$subject = $admin->add_slashes(strip_tags((string)$miniformScalar('subject','')));
	$confirm_user = (int)$miniformScalar('confirm_user',0);
	$confirm_subject = $admin->add_slashes(strip_tags((string)$miniformScalar('confirm_subject','')));
	$template = $admin->add_slashes(strip_tags((string)$miniformScalar('template','')));
	$no_store = (int)$miniformScalar('no_store',0);
	$use_ajax = (int)$miniformScalar('use_ajax',0);
	// $disable_tls = (int)$_POST['disable_tls'];
	$use_recaptcha = (int)$miniformScalar('use_recaptcha',0);
	$recaptcha_key = $admin->add_slashes(strip_tags((string)$miniformScalar('recaptcha_key','')));
	$recaptcha_secret = $admin->add_slashes(strip_tags((string)$miniformScalar('recaptcha_secret','')));
	$success = (int)$miniformScalar('successpage',0);
	$upload_limit_mb = max(1, min(512, (int)$miniformScalar('upload_limit_mb',512)));
	
	$query = "UPDATE ".TABLE_PREFIX."mod_miniform SET 
			`email` = '$email', 
			`emailfrom` = '$emailfrom', 
			`subject` = '$subject', 
			`confirm_user` = '$confirm_user', 
			`confirm_subject` = '$confirm_subject', 
			`successpage` = '$success', 
			`template` = '$template',
			`no_store` = '$no_store',
			`use_ajax` = '$use_ajax',
			`use_recaptcha` = '$use_recaptcha',
			`recaptcha_key` = '$recaptcha_key',
			`recaptcha_secret` = '$recaptcha_secret',
			`upload_limit_mb` = '$upload_limit_mb'
			WHERE `section_id` = '$section_id'";
	$database->query($query);	
}

// Check if there is a database error, otherwise say successful
if($database->is_error()) {
	if ($miniformAjax) {
		header('Content-Type: application/json; charset=UTF-8');
		header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
		http_response_code(500);
		echo json_encode(array('success' => false, 'message' => $MF['SAVE_FAILED'], 'ftan' => $admin->getFTAN()));
		exit;
	}
	$admin->print_error($database->get_error(), $js_back);
} else {
	if ($miniformAjax) {
		header('Content-Type: application/json; charset=UTF-8');
		header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
		echo json_encode(array('success' => true, 'message' => $MESSAGE['PAGES_SAVED'], 'ftan' => $admin->getFTAN()));
		exit;
	}
	$admin->print_success($MESSAGE['PAGES_SAVED'], ADMIN_URL.'/pages/modify.php?page_id='.$page_id);
}

// Print admin footer
$admin->print_footer();

?>
