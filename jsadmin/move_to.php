<?php
ob_start();
require_once dirname(__DIR__, 2).'/config.php';
require_once WB_PATH.'/framework/class.admin.php';
$securityAdmin = new admin('Pages', 'pages_modify', false);

function jsadmin_move_query_ok($result)
{
    return is_object($result) && (!method_exists($result,'error') || (string)$result->error()==='');
}

function jsadmin_move_row($result)
{
    if(!jsadmin_move_query_ok($result) || !method_exists($result,'fetchRow'))return null;
    $row=$result->fetchRow(MYSQLI_ASSOC);
    return is_array($row)?$row:null;
}

if (!isset($_SERVER['REQUEST_METHOD']) || !is_string($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    if (ob_get_level()) ob_end_clean();
    http_response_code(405);
    exit;
}
if (!$securityAdmin->is_authenticated() || !$securityAdmin->get_permission('jsadmin','module') || !$securityAdmin->checkFTAN('POST')) {
    if (ob_get_level()) ob_end_clean();
    http_response_code(403);
    exit;
}

$pageId = filter_input(INPUT_POST, 'page_id', FILTER_VALIDATE_INT);
$position = filter_input(INPUT_POST, 'position', FILTER_VALIDATE_INT);
if (!$pageId || !$position || $position < 1 || $position > 1000000) {
    if (ob_get_level()) ob_end_clean();
    http_response_code(422);
    exit;
}

$page_id = (int)$pageId;
$update_when_modified = true;
$admin_header = false;
require WB_PATH.'/modules/admin.php';

$sectionId = filter_input(INPUT_POST, 'section_id', FILTER_VALIDATE_INT);
$fileId = filter_input(INPUT_POST, 'file_id', FILTER_VALIDATE_INT);
$groupId = filter_input(INPUT_POST, 'group_id', FILTER_VALIDATE_INT);
if ($groupId) {
    $id = (int)$groupId;
    $idField = 'group_id';
    $commonField = 'section_id';
    $table = TABLE_PREFIX.'mod_download_gallery_groups';
} elseif ($fileId) {
    $id = (int)$fileId;
    $idField = 'file_id';
    $commonField = 'group_id';
    $table = TABLE_PREFIX.'mod_download_gallery_files';
} elseif ($sectionId) {
    $id = (int)$sectionId;
    $idField = 'section_id';
    $commonField = 'page_id';
    $table = TABLE_PREFIX.'sections';
} else {
    $id = $page_id;
    $idField = 'page_id';
    $commonField = 'parent';
    $table = TABLE_PREFIX.'pages';
}

$result = $database->query("SELECT `$commonField`,`position` FROM `$table` WHERE `$idField`=$id LIMIT 1");
$row = jsadmin_move_row($result);
if (!$row) {
    if (ob_get_level()) ob_end_clean();
    http_response_code(404);
    exit;
}
$commonId = (int)$row[$commonField];
$oldPosition = (int)$row['position'];
if ($groupId || $fileId) {
    $relatedSection = $groupId ? $commonId : 0;
    if ($fileId) {
        $groupResult = $database->query("SELECT `section_id` FROM `".TABLE_PREFIX."mod_download_gallery_groups` WHERE `group_id`=$commonId LIMIT 1");
        $groupRow = jsadmin_move_row($groupResult);
        $relatedSection = $groupRow ? (int)$groupRow['section_id'] : 0;
    }
    $sectionResult = $relatedSection ? $database->query("SELECT `page_id` FROM `".TABLE_PREFIX."sections` WHERE `section_id`=$relatedSection LIMIT 1") : null;
    $sectionRow = jsadmin_move_row($sectionResult);
    if (!$sectionRow || (int)$sectionRow['page_id'] !== $page_id) {
        if (ob_get_level()) ob_end_clean();
        http_response_code(403);
        exit;
    }
}
if ($oldPosition !== (int)$position) {
	if (!jsadmin_move_query_ok($database->query('START TRANSACTION'))) {
		if (ob_get_level()) ob_end_clean();
		http_response_code(500);
		exit;
	}
    if ($oldPosition < $position) {
        $sql = "UPDATE `$table` SET `position`=`position`-1 WHERE `position`>$oldPosition AND `position`<=".(int)$position." AND `$commonField`=$commonId";
    } else {
        $sql = "UPDATE `$table` SET `position`=`position`+1 WHERE `position`>=".(int)$position." AND `position`<$oldPosition AND `$commonField`=$commonId";
    }
	$shiftResult=$database->query($sql);
	$moveResult=jsadmin_move_query_ok($shiftResult)?$database->query("UPDATE `$table` SET `position`=".(int)$position." WHERE `$idField`=$id"):false;
    if (!jsadmin_move_query_ok($shiftResult) || !jsadmin_move_query_ok($moveResult) || !jsadmin_move_query_ok($database->query('COMMIT'))) {
		$database->query('ROLLBACK');
        if (ob_get_level()) ob_end_clean();
        http_response_code(500);
        exit;
    }
}
if (ob_get_level()) ob_end_clean();
http_response_code(204);
