<?php

// initialize json_respond array  (will be sent back)
$aJsonRespond = array();
$aJsonRespond['message'] = 'Nothing to do!';
$aJsonRespond['success'] = false;

// require config for initialisation.
require '../../config.php';
$miniformLanguage = defined('LANGUAGE') ? strtoupper(substr((string) LANGUAGE, 0, 2)) : 'EN';
$miniformLanguage = preg_replace('/[^A-Z]/', '', $miniformLanguage);
require __DIR__.'/languages/EN.php';
if ($miniformLanguage !== 'EN' && is_file(__DIR__.'/languages/'.$miniformLanguage.'.php')) require __DIR__.'/languages/'.$miniformLanguage.'.php';

// test admin access for this
require_once(WB_PATH.'/framework/class.admin.php');
$admin = new admin('Pages', 'pages_modify', false, true);

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

if (!$admin->is_authenticated() || !$admin->get_permission('miniform', 'module') || !$admin->checkFTAN()) {
	http_response_code(403);
	$aJsonRespond['message'] = $MF['SECURITY_FAILED'];
	$aJsonRespond['ftan'] = $admin->getFTAN();
	die(json_encode($aJsonRespond));
}
 
// check for the iRecordID parameter. Preventing warnings in the errorlogs
if(!isset($_POST['iRecordID']) || !is_scalar($_POST['iRecordID'])) die(json_encode($aJsonRespond));

// get record_id to delete
$iRecordID      = intval($_POST['iRecordID']);

if($iRecordID > 0) {
	// build query
	$query = "DELETE FROM `".TABLE_PREFIX."mod_miniform_data` WHERE `message_id` = '".$iRecordID."' LIMIT 1";
	// excecute query
	$result = $database->query($query);
	// test for errors
	if($result === false || $database->is_error()) {
		$aJsonRespond['message'] = $MF['DELETE_FAILED'];
	} else {
		$aJsonRespond['message'] = $MF['DELETE_SUCCESS'];
		$aJsonRespond['success'] = true;
	}
}
$aJsonRespond['ftan'] = $admin->getFTAN();
// return json data
die(json_encode($aJsonRespond));
