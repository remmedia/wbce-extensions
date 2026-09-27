<?php
header('Content-Type: application/json; charset=UTF-8');

// json_respond array wich is sent back to the backend
$aRspnd = array();
$aRspnd['message'] = '';
$aRspnd['success'] = false;
#exit(json_encode($aRspnd));

if(is_readable($sConfigFile = '../../config.php')) {
    $aRspnd['message'] = 'L: '.__LINE__;
    require_once($sConfigFile);
} else {
    $aRspnd['message'] = 'L: '.__LINE__;
    exit(json_encode($aRspnd));
}

// check if user has permissions to access the outputfilter_dashboard module
require_once(WB_PATH.'/framework/Admin.php');
$admin = new admin('admintools', 'admintools', false, false);
require_once __DIR__.'/languages/EN.php';
$dropletsLanguage = defined('LANGUAGE') ? __DIR__.'/languages/'.LANGUAGE.'.php' : '';
if ($dropletsLanguage !== '' && is_file($dropletsLanguage)) {
    require $dropletsLanguage;
}
require_once __DIR__.'/functions.inc.php';
if (!isset($_POST['action'])) {
    http_response_code(400);
    exit(json_encode(array('success' => false, 'message' => $DR_TEXT['ASYNC_MISSING_ACTION'])));
}
if (!($admin->is_authenticated() && $admin->get_permission('droplets', 'module'))) {
    http_response_code(403);
    $aRspnd['message'] = $DR_TEXT['ASYNC_FORBIDDEN'];
    exit(json_encode($aRspnd));
}
if (!$admin->checkFTAN()) {
    http_response_code(403);
    $aRspnd['message'] = $DR_TEXT['ASYNC_SECURITY_FAILED'];
    $aRspnd['ftan'] = $admin->getFTAN();
    exit(json_encode($aRspnd));
}

// Sanitize variables
$purpose = isset($_POST['purpose']) ? (string) $_POST['purpose'] : '';
if ($purpose == "toggle_status") {
    $iFilterID = isset($_POST['idkey']) && is_scalar($_POST['idkey']) ? (string) $_POST['idkey'] : '';
    $iId = $admin->checkIDKEY($iFilterID, 0, 'POST', true);
    if($iId == 0){
        $aRspnd['message'] = $DR_TEXT['MISSING_ID'];
        $aRspnd['success'] = false;
        $aRspnd['ftan'] = $admin->getFTAN();
        exit(json_encode($aRspnd));
    }
    $iActive = ((string) $_POST['action'] === '1') ? 1 : 0;
    
    if(!$database->updateRow('{TP}mod_droplets', 'id', [
        'id' => $iId,
        'active' => $iActive,
    ])) {
        $aRspnd['success'] = false;
        $aRspnd['message'] = $DR_TEXT['ASYNC_SAVE_FAILED'];
        $aRspnd['icon'] = 'cancel.gif';
        $aRspnd['ftan'] = $admin->getFTAN();
        exit(json_encode($aRspnd));
    } else {
        $aRspnd['message'] = $DR_TEXT['ASYNC_STATUS_SAVED'];
        $aRspnd['success'] = true;
        $aRspnd['ftan'] = $admin->getFTAN();
        exit(json_encode($aRspnd));
    }
} elseif ($purpose === 'show_date') {
    $showDate = isset($_POST['action']) && (string) $_POST['action'] === '1';
    Settings::Set('droplets_show_by_date', $showDate);
    $aRspnd['success'] = true;
    $aRspnd['message'] = $DR_TEXT['ASYNC_DISPLAY_SAVED'];
    $aRspnd['ftan'] = $admin->getFTAN();
    exit(json_encode($aRspnd));
} elseif ($purpose === 'delete') {
    $iFilterID = isset($_POST['idkey']) && is_scalar($_POST['idkey']) ? (string) $_POST['idkey'] : '';
    $iId = $admin->checkIDKEY($iFilterID, 0, 'POST', true);
    if (!$iId) {
        http_response_code(422);
        $aRspnd['message'] = $DR_TEXT['MISSING_ID'];
    } else {
        $_POST['markeddroplet'] = array((int) $iId);
        $deleted = wbce_delete_droplets();
        $aRspnd['success'] = $deleted !== false && !$database->is_error();
        $aRspnd['message'] = $aRspnd['success'] ? $DR_TEXT['DELETED'] : $DR_TEXT['ASYNC_DELETE_FAILED'];
    }
    $aRspnd['ftan'] = $admin->getFTAN();
    exit(json_encode($aRspnd));
} else {
    $aRspnd['message'] = $DR_TEXT['ASYNC_INVALID_REQUEST'];
    exit(json_encode($aRspnd));
}
