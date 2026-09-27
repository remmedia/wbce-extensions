<?php
require dirname(__DIR__,2).'/config.php';
require_once WB_PATH.'/framework/Admin.php';
require_once __DIR__.'/Language.php';
header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store');
$admin = new admin('Admintools','admintools',false,false);
if(!$admin->is_authenticated()||!$admin->get_permission('security_center','module')){
    http_response_code(403);
    echo json_encode(array('error'=>WbceSecurityCenterLanguage::text('actions.security_failed','Security check failed.')),JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT);
    exit;
}
$translations=WbceSecurityCenterLanguage::all();
echo json_encode((array)($translations['client']??array()),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT);
