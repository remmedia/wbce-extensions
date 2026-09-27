<?php
defined('WB_PATH') or die('No direct access');
$PRECHECK = array(
    'WBCE_VERSION' => array('VERSION' => '1.6.8', 'OPERATOR' => '>='),
    'PHP_VERSION' => array('VERSION' => '8.2.0', 'OPERATOR' => '>='),
    'WB_ADDONS' => array('two_factor' => array('VERSION' => '1.1.32', 'OPERATOR' => '>=')),
);
