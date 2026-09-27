<?php
defined('WB_PATH') or die('No direct access');

$PRECHECK = array();
$PRECHECK['WBCE_VERSION'] = array('VERSION' => '1.6.8', 'OPERATOR' => '>=');
$PRECHECK['PHP_VERSION'] = array('VERSION' => '8.2.0', 'OPERATOR' => '>=');
$PRECHECK['CUSTOM_CHECKS'] = array(
    'PHP cURL' => array(
        'REQUIRED' => 'installed',
        'ACTUAL' => function_exists('curl_init') ? 'installed' : 'not installed',
        'STATUS' => function_exists('curl_init'),
    ),
    'SHA-256' => array(
        'REQUIRED' => 'available',
        'ACTUAL' => in_array('sha256', hash_algos(), true) ? 'available' : 'not available',
        'STATUS' => in_array('sha256', hash_algos(), true),
    ),
);
