<?php
/**
 * WebsiteBaker CMS AdminTool: wbSeoTool
 *
 * ajax/save.php
 * This file gets $_POST Data sent by ajax and executes DB updates on fields
 * 
 * 
 * @platform    CMS WebsiteBaker 2.8.x
 * @package     wbSeoTool
 * @author      Christian M. Stefan (Stefek)
 * @copyright   Christian M. Stefan
 * @license     http://www.gnu.org/licenses/gpl-2.0.html
 */

require('../../../config.php');
require_once(WB_PATH.'/framework/class.admin.php');
require_once dirname(__DIR__).'/compat.php';
header('Content-Type: text/plain; charset=UTF-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
	http_response_code(405);
	exit;
}

$bAdminHeader = FALSE; // suppress to print the header, so no new FTAN will be set
$admin = new admin('Pages', 'pages_settings', $bAdminHeader);
// check if user can change things to avoid any submission from a logged in not admin user
if($admin->get_permission('pages_modify') == false ) { 
	http_response_code(403);
	exit;
}
if (!$admin->checkFTAN()) {
	http_response_code(403);
	exit;
}

// Create the Fields from Submission
$aFromString = explode('-', isset($_POST['id']) ? (string) $_POST['id'] : '', 2);
if (count($aFromString) !== 2) {
	http_response_code(400);
	exit;
}
$sDbField    = $aFromString[0];
$iPageId     = intval($aFromString[1]);
//sanitize new value to update
$sNewValue = str_replace(array("[[", "]]", "\n", "\t"), '', htmlspecialchars((string) $admin->get_post('value'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
$aCheckPagesFields = array('page_title', 'description', 'keywords');

//	GET TOOL SETTINGS FROM DB (Json Array)
$jsonSettings = wb_seo_db_value($database, "SELECT `settings_json` FROM `".TABLE_PREFIX."mod_page_seo_tool`");
$aSettings = json_decode((string) $jsonSettings, TRUE);
if (!is_array($aSettings)) { $aSettings = array(); }

if(!defined('REWRITE_URL') && !empty($aSettings['rewriteUrl']['use']) && !empty($aSettings['rewriteUrl']['dbString'])){
	define('REWRITE_URL', $aSettings['rewriteUrl']['dbString']);
	array_push($aCheckPagesFields, REWRITE_URL);
}

// UPDATE the DB Field
 if(isset($_POST['value']) && in_array($sDbField, $aCheckPagesFields)){
	// Update page settings in the pages table
	$sUpdateQuery  = 'UPDATE `'.TABLE_PREFIX.'pages` SET `'.$sDbField.'` = "'.$database->escapeString($sNewValue).'" WHERE `page_id` = '.$iPageId;
	$database->query($sUpdateQuery);
}
if($database->is_error() == FALSE) {
	echo $sNewValue;
}
exit;
