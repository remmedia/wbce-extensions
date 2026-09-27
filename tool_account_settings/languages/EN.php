<?php
/**
 * This file provides the german translation of the multilingual array for this Tool
 * 
 * 
 * @author      Christian M. Stefan (Stefek)
 * @copyright   Christian M. Stefan
 * @license     http://www.gnu.org/licenses/gpl-2.0.html
 */

$module_name        = 'Account and registration settings';
$module_description = 'Admin tool for configuring account creation and user registration.';

////////////////////////////////////////////////////////////////////////////////////
//              ENGLISH LANGUAGE STRINGS FOR 'Tool Account Settings'              //
////////////////////////////////////////////////////////////////////////////////////

$TOOL_TXT['OVERVIEW_DESCRIPTION'] = 'List of all the users registered to the system.';
$TOOL_TXT['NO_USERS'] = 'There are no users to display.';
$TOOL_TXT['CREATE_USER'] = 'Create new user';

$TOOL_TXT['PREFERENCES'] = 'My Preferences';
$TOOL_TXT['HEADING']    = 'UserBase Admin-Tool';
$TOOL_TXT['EDIT']       = 'Edit user';
$TOOL_TXT['DELETE']     = 'Delete user';
$TOOL_TXT['NEW']        = 'Add new user';

$TOOL_TXT['SAVE_SETTINGS'] = 'save details';
$TOOL_TXT['SAVE_EMAIL']    = 'save email';
$TOOL_TXT['SAVE_PASSWORD'] = 'save password';
$TOOL_TXT['SAVING']        = 'Saving …';
$TOOL_TXT['ASYNC_SAVED']   = 'Settings were saved.';
$TOOL_TXT['ASYNC_FAILED']  = 'Settings could not be saved.';
$TOOL_TXT['NAV_LABEL']     = 'Account settings';
$TOOL_TXT['NOT_AVAILABLE'] = 'Not available';
$TOOL_TXT['VIEW_NOT_FOUND'] = 'The requested view is unavailable.';
$TOOL_TXT['RESET_NEUTRAL'] = 'If an active account belongs to this address, a reset link was sent.';
$TOOL_TXT['RESET_MAIL_SUBJECT'] = 'Reset password for %s';
$TOOL_TXT['RESET_MAIL_BODY'] = "Hello %s,\n\nUse this link within one hour to choose a new password:\n\n%s\n\nIf you did not request this, you can ignore this email.";
$TOOL_TXT['SECURE_TOKEN_FAILED'] = 'A secure confirmation token could not be generated. Please try again later.';

$TOOL_TXT['EXPORT']     = 'Export';
$TOOL_TXT['CLOSE']      = "Close";
$TOOL_TXT['SAVE_PREFERENCES'] = "Save";
$TOOL_TXT['USER_ID']    = 'User-ID';
$TOOL_TXT['GROUP']      = 'Group'; 
$TOOL_TXT['GROUPS']     = 'Groups';
$TOOL_TXT['CONFIG']     = 'Configuration';
 
$TOOL_TXT['OVERVIEW']   = 'Overview';
$TOOL_TXT['USERLINK']   = 'User';
$TOOL_TXT['ACTIVATE']   = 'Activate';
$TOOL_TXT['DEACTIVATE'] = 'Deactivate';

$TOOL_TXT['USER_CORE_ACTIVATED']   = 'activated?';
$TOOL_TXT['MAIN_CONFIG']           = 'Main Settings';
$TOOL_TXT['USER_CORE_ACTIVE']      = 'User is activated'; 
$TOOL_TXT['USER_CORE_INACTIVE']    = 'User is deactivated';
$TOOL_TXT['ACCOUNTS_CONFIG']       = '[root]/account/ Configuration';
$TOOL_TXT['CONFIG_USER_DIR']       = 'Configuration for the <i>[root]/account/</i> directory';
$TOOL_TXT['USERBASE_ACTIVE']       = 'User has a extended profile';
$TOOL_TXT['USERBASE_INACTIVE']     = 'User has no extended profile';

$TOOL_TXT['WARNING_USER_SELECTION'] = 'User Selection failed!';

$TOOL_TXT['USER_DETAILS']          = 'User Details';
$TOOL_TXT['SEE_PROFILE']           = 'See &amp; modify profile';
$TOOL_TXT['EXTENDED_PROFILE']      = 'Extended profile';
$TOOL_TXT['SEARCH_EXTEND_ONLY']    = 'Only users with extend';
$TOOL_TXT['LATEST_LOGIN']          = 'Latest login';
$TOOL_TXT['DATE_REGISTERED']       = 'Registration date';

$TOOL_TXT['PLEASE_SELECT']         = 'select';
$TOOL_TXT['DETAILS_SAVED']         = 'details successfully saved';
$TOOL_TXT['SAVE_SETTINGS']         = 'save details';
$TOOL_TXT['SAVE_EMAIL']            = 'save email';
$TOOL_TXT['SAVE_PASSWORD']         = 'save password';

$TOOL_TXT['HEADING_ERROR']         = 'Error';
$TOOL_TXT['GENERIC_ERROR_MESSAGE'] = 'The account has not been confirmed yet, is already activated or invalid data was submitted.';
$TOOL_TXT['CONTACT_ADMINISTRATOR'] = 'If necessary, please contact the website administrator for further assistance.';

$MESSAGE['DISPLAY_NAME_EMPTY'] = 'Please enter a value for "Display Name".';
$MESSAGE['GDPR_AGREEMENT_MANDATORY'] = 'You need to agree upon storing and processing of data in order to sign up to our services.';

$TEXT['REGISTER_THANKYOU'] = 'Thank you for registering on our website.';
$TEXT['REGISTER_CHECK_MAIL_ACTIVATION_USER'] = 'Please check your emails now and click on the confirmation link in the email we just have sent to you.';
$TEXT['REGISTER_GENEREC_EMAIL_NOT_RECIEVED'] =' If you did not receive the email, please wait some minutes and/or look into your spam folder too.';
$TEXT['REGISTER_LOGIN_SENT_TO_USER'] = 'Your login data was just sent to your email address.';
$TEXT['REGISTER_USER_ACTIVATED'] = 'The account has been activated. The login data was sent to the corresponding email address.';
$TEXT['REGISTER_ACTIVATION_PENDING'] = 'Please be patient. You will receive an email with your login data when we have approved and activated your account.';
$TEXT['REGISTER_GDPR_PHRASE'] = 'I confirm that I have read and that I accept the privacy policy. I agree upon storing and processing of the supplied personal data.';


/////////////////////////////////////////////////////////////////////////
//    Override language strings found in [WB_URL]/languages/EN.php     //
/////////////////////////////////////////////////////////////////////////
$MENU['PREFERENCES']    = 'My profile';
$TEXT['ACCOUNT_SIGNUP'] = 'Account Sign-Up';
$TEXT['SIGNUP'] = 'Sign-up';
