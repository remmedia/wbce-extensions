<?php
defined('WB_PATH') or die('No direct access');
$nativeHooks=(function_exists('wbce_add_action')&&function_exists('wbce_add_filter'))||(defined('WBCE_VERSION')&&version_compare((string)WBCE_VERSION,'1.7.0','>='));
$bridgeResult=!$nativeHooks&&isset($database)&&is_object($database)&&is_callable(array($database,'query'))?$database->query("SELECT `version` FROM `{TP}addons` WHERE `type`='module' AND `directory`='wbce_hook_bridge' LIMIT 1"):null;
$bridgeRow=null;
if(is_object($bridgeResult)&&is_callable(array($bridgeResult,'fetchRow')))$bridgeRow=$bridgeResult->fetchRow(defined('MYSQLI_ASSOC')?MYSQLI_ASSOC:1);
elseif(is_object($bridgeResult)&&is_callable(array($bridgeResult,'fetch_assoc')))$bridgeRow=$bridgeResult->fetch_assoc();
$hooksAvailable=$nativeHooks||($bridgeRow&&version_compare((string)$bridgeRow['version'],'1.2.0','>='));
$PRECHECK=array(
    'PHP_VERSION'=>array('VERSION'=>'8.2.0','OPERATOR'=>'>='),
    'WBCE_VERSION'=>array('VERSION'=>'1.6.8','OPERATOR'=>'>='),
    'WB_ADDONS'=>array(
        'worker'=>array('VERSION'=>'1.10.11','OPERATOR'=>'>='),
    ),
    'CUSTOM_CHECKS'=>array(
        'Hook-Schnittstellen'=>array(
            'REQUIRED'=>'WBCE 1.7.0 oder Hook-Bridge 1.2.0',
            'ACTUAL'=>$hooksAvailable?'Verfügbar':'Nicht verfügbar',
            'STATUS'=>$hooksAvailable
        ),
    ),
);
