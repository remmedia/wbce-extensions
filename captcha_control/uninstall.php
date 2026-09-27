<?php
/**
 * WBCE CMS
 * Way Better Content Editing.
 * Visit https://wbce.org to learn more and to join the community.
 *
 * @copyright Ryan Djurovich (2004-2009)
 * @copyright WebsiteBaker Org. e.V. (2009-2015)
 * @copyright WBCE Project (2015-)
 * @license GNU GPL2 (or any later version)
 */

// no direct file access
if (count(get_included_files())==1) {
    header("Location: ../index.php", true, 301);
    exit;
}

// Current format
require_once __DIR__ . '/Compatibility.php';
wbce_captcha_setting_delete('enabled_captcha');
wbce_captcha_setting_delete('enabled_asp');
wbce_captcha_setting_delete('captcha_type');
wbce_captcha_setting_delete('captcha_altcha');
wbce_captcha_setting_delete('captcha_login_mode');
wbce_captcha_setting_delete('captcha_password_reset');

// Legacy keys — left over from older versions or incomplete migrations
foreach ([
    'captcha_altcha_hmac_key', 'captcha_altcha_max', 'captcha_altcha_ttl',
    'captcha_cfg',
    'asp_session_min_age', 'asp_view_min_age', 'asp_input_min_age', 'ct_text',
] as $key) {
    if (wbce_captcha_setting_exists($key)) {
        wbce_captcha_setting_delete($key);
    }
}
