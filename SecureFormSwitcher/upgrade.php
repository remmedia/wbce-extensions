<?php
/**
 * @category        modules
 * @package         Secure Form Switcher
 * @author          WBCE Project
 * @copyright       Norbert Heimsath
 * @license			WTFPL
 */

//no direct file access
if (count(get_included_files())==1) {
    header("Location: ../index.php", true, 301);
}

// Del old switch as now there is only one.
$upgradeError=Settings::Del("secure_form_module");

$timeout = defined('WB_SECFORM_TIMEOUT') ? WB_SECFORM_TIMEOUT : Settings::Get('wb_secform_timeout', '7200');
$upgradeError=Settings::Set("wb_session_timeout",$timeout);
if($upgradeError)throw new RuntimeException((string)$upgradeError);
