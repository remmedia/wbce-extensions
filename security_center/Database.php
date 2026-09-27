<?php
function wbce_security_center_database($candidate=null)
{
    if(is_object($candidate)&&is_callable(array($candidate,'query')))return $candidate;
    $candidate=$GLOBALS['database']??null;if(is_object($candidate)&&is_callable(array($candidate,'query')))return $candidate;
    if(!class_exists('database')&&defined('WB_PATH')&&is_file(WB_PATH.'/framework/Database.php'))require_once WB_PATH.'/framework/Database.php';
    if(class_exists('database'))return new database();
    if(is_file(__DIR__.'/Language.php'))require_once __DIR__.'/Language.php';
    $message=class_exists('WbceSecurityCenterLanguage',false)
        ?WbceSecurityCenterLanguage::text('errors.database_unavailable','The WBCE database connection is unavailable.')
        :'The WBCE database connection is unavailable.';
    throw new RuntimeException($message);
}
