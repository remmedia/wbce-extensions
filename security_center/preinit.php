<?php
if(!defined('WB_PATH'))return;
require_once __DIR__.'/RuntimeConfig.php';require_once __DIR__.'/PhpRuntime.php';
$securityCenterRuntime=WbceSecurityCenterRuntimeConfig::load();
WbceSecurityCenterPhpRuntime::apply($securityCenterRuntime);
// Local emergency switch; only somebody with filesystem access can use it.
if(is_file(WB_PATH.'/temp/security-center-disable.flag'))return;
require_once __DIR__.'/Firewall.php';require_once __DIR__.'/TrafficBuffer.php';
try{global $database;WbceSecurityCenterFirewall::protect($database,$securityCenterRuntime);WbceSecurityCenterTrafficBuffer::capture($securityCenterRuntime);}catch(Throwable $exception){error_log('WBCE Security Center firewall: '.$exception->getMessage());}
