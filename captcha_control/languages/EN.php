<?php
/**
 * captcha_control — EN.php
 *
 * @copyright WBCE Project (2015-)
 * @license   GNU GPL2 (or any later version)
 */

$module_name = 'Captcha &amp; Spam Protection';
$module_description = 'Global CAPTCHA and spam-protection management extended through installable providers.';

// ── General ──────────────────────────────────────────────────────────────────
$CAPTCHA['HEADING']            = 'Captcha &amp; Spam Protection';
$CAPTCHA['HOWTO']              = 'Configure the ALTCHA proof-of-work captcha and the Honeypot spam filter. Both protect forms across the site without any user friction.';
$CAPTCHA['CAPTCHA_TYPE']       = 'Captcha type';
$CAPTCHA['CAPTCHA_EXP']        = 'ALTCHA is a self-hosted, privacy-friendly proof-of-work captcha. No third-party service required.';
$CAPTCHA['USE_SIGNUP_CAPTCHA'] = 'Enable captcha for registrations';
$CAPTCHA['ADMIN_TOOLS']        = 'Admin Tools';
$CAPTCHA['CONFIGURE_PROTECTION'] = 'Configure protection';
$CAPTCHA['ACTIVE']             = 'Active';
$CAPTCHA['AVAILABLE']          = 'Available';
$CAPTCHA['INSTALLED_PROVIDER'] = 'Installed CAPTCHA provider';
$CAPTCHA['ACTIVE_PROVIDER']    = 'Active provider:';
$CAPTCHA['NO_PROVIDER_TITLE']  = 'No CAPTCHA provider is installed.';
$CAPTCHA['NO_PROVIDER_INFO']   = 'Please install at least one provider. ALTCHA is added automatically during installation from the Store.';
$CAPTCHA['PROVIDER_DEFAULT_INFO'] = 'The selected CAPTCHA provider protects the configured areas against automated requests.';
$CAPTCHA['LOGIN_CAPTCHA']      = 'CAPTCHA for login';
$CAPTCHA['LOGIN_AFTER_FAILURES'] = 'After failed logins';
$CAPTCHA['LOGIN_ALWAYS']       = 'Always';
$CAPTCHA['PASSWORD_RESET_CAPTCHA'] = 'CAPTCHA for password reset';
$CAPTCHA['SAVING']             = 'Saving …';
$CAPTCHA['SAVE_SUCCESS']       = 'Settings saved.';
$CAPTCHA['SAVE_FAILED']        = 'Saving failed:';
$CAPTCHA['INVALID_RESPONSE']   = 'Invalid server response';
$CAPTCHA['TWIG_FAILED']        = 'The Twig compatibility layer could not load the CAPTCHA template.';
$CAPTCHA['TWIG_REQUIRED']      = 'This WBCE version requires the Hook Bridge with Twig compatibility.';
$CAPTCHA['FALLBACK_PROVIDER_NAME'] = 'ALTCHA (fallback)';
$CAPTCHA['FALLBACK_PROVIDER_DESCRIPTION'] = 'Built-in emergency provider; installed provider modules replace it automatically.';
$CAPTCHA['ENABLED'] = 'Enabled';
$CAPTCHA['DISABLED'] = 'Disabled';
$CAPTCHA['NO_SESSION'] = 'Captcha requires an active session.';
$CAPTCHA['LEAVE_EMPTY'] = 'Leave this field empty';
$CAPTCHA['PRECHECK_HOOKS_REQUIRED'] = 'WBCE 1.7.0 or Hook Bridge 1.2.0';
$CAPTCHA['PRECHECK_HOOKS_LABEL'] = 'Hook interfaces';
$CAPTCHA['PRECHECK_AVAILABLE'] = 'Available';
$CAPTCHA['PRECHECK_UNAVAILABLE'] = 'Unavailable';

// ── Advanced Spam Protection ──────────────────────────────────────────────────
$CAPTCHA['ASP_LABEL']             = 'Advanced Spam Protection (Honeypot)';
$CAPTCHA['ASP_DESCRIPTION']       = 'A hidden field catches bots that auto-fill all inputs. Requires no user action. Works independently of the captcha above.';
$CAPTCHA['MODULES_SETTINGS_INFO'] = '<b>IMPORTANT:</b> Individual modules such as <i>MiniForm</i>, <i>Guestbook</i>, etc. have their own settings for whether Captcha should be used in the module\'s form. <br><b>Please check the settings of the respective modules</b>.';

// ── Widget customization ──────────────────────────────────────────────────────
$CAPTCHA['WIDGET_HEADING']     = 'Widget Customization';
$CAPTCHA['AUTO_LABEL']         = 'Start Mode';
$CAPTCHA['AUTO_OFF']           = 'Manual (click)';
$CAPTCHA['AUTO_ONLOAD']        = 'Automatic';
$CAPTCHA['AUTO_ONSUBMIT']      = 'On form submit';
$CAPTCHA['DELAY_LABEL']        = 'Delay';
$CAPTCHA['DELAY_HINT']         = 'ms pause before PoW starts — makes automated attacks harder';
$CAPTCHA['HIDEFOOTER']         = 'Hide "Powered by ALTCHA" footer';
$CAPTCHA['HIDELOGO']           = 'Hide ALTCHA logo';
$CAPTCHA['COLOR_BRAND']        = 'Accent colour';
$CAPTCHA['COLOR_BRAND_HINT']   = 'Spinner &amp; border';
$CAPTCHA['COLOR_SUCCESS']      = 'Check colour';
$CAPTCHA['COLOR_BASE']         = 'Widget background';
$CAPTCHA['COLOR_CHECKBOX']     = 'Checkbox background';
$CAPTCHA['COLOR_TEXT']         = 'Text colour';
$CAPTCHA['BORDER_RADIUS']      = 'Corner radius';
$CAPTCHA['COLOR_DEFAULT']      = 'Default';
$CAPTCHA['CORNER_SQUARE']      = 'Square';
$CAPTCHA['CORNER_LIGHT']       = 'Slight';
$CAPTCHA['CORNER_ROUND']       = 'Round';
$CAPTCHA['PREVIEW']            = 'Preview';
$CAPTCHA['WIDGET_FOOTER_TEXT'] = 'Protected by ALTCHA';
$CAPTCHA['WIDGET_CHECK_TEXT']  = 'I am not a robot';

// ── No HTTPS fallback ────────────────────────────────────────────────────────
$CAPTCHA['NO_HTTPS_HEADING']       = 'No settings available — HTTPS required';
$CAPTCHA['NO_HTTPS_INFO']          = 'ALTCHA relies on a browser feature (Web Crypto) that only works on HTTPS sites. This site is currently reachable over plain HTTP, so the widget could never complete its check — there is nothing to configure here until an SSL certificate is installed and the site is switched to HTTPS.';
$CAPTCHA['NO_HTTPS_FALLBACK']      = 'In the meantime, a simple math captcha (e.g. "3 + 5 = ?") is used automatically instead, so forms stay protected.';
$CAPTCHA['VERIFICATION_INFO_RES']  = 'Please solve the calculation to verify you are human.';
