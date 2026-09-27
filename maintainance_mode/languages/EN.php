<?php
/**
 * @category        modules
 * @package         Maintenance Mode
 * @author          WBCE Project
 * @copyright       Norbert Heimsath
 * @license         WTFPL
 */
 
// module description 
$module_description = 'Activating the maintenance mode will replace the frontend view temporarily by a "Under construction" page.';

// Headings and text outputs
$MOD_MAINTAINANCE['HEADER'] =      'Maintenance Mode';
$MOD_MAINTAINANCE['DESCRIPTION'] = 'When maintenance mode is enabled, visitors see the maintenance page instead of the website. A signed-in super administrator can continue to view the regular frontend. You can customize the page in the active frontend template at <code>systemplates/maintainance.tpl.php</code>.';
$MOD_MAINTAINANCE['CHECKBOX'] =    'Activate maintenance mode';
$MOD_MAINTAINANCE['ENABLED'] = 'Enabled';
$MOD_MAINTAINANCE['DISABLED'] = 'Disabled';
$MOD_MAINTAINANCE['ENABLED_SAVED'] = 'Maintenance mode has been enabled.';
$MOD_MAINTAINANCE['DISABLED_SAVED'] = 'Maintenance mode has been disabled.';
$MOD_MAINTAINANCE['SAVE_FAILED'] = 'Maintenance mode could not be changed.';
$MOD_MAINTAINANCE['FORBIDDEN'] = 'You are not allowed to change maintenance mode.';
$MOD_MAINTAINANCE['SECURITY_ERROR'] = 'The security token is invalid or has expired.';
$MOD_MAINTAINANCE['INVALID_RESPONSE'] = 'The server returned an invalid response.';
$MOD_MAINTAINANCE['CONFIGURATION_UNAVAILABLE'] = 'The WBCE configuration is unavailable.';
$MOD_MAINTAINANCE['SAVE_NOT_CONFIRMED'] = 'WBCE did not confirm the maintenance-mode change.';
$MOD_MAINTAINANCE['INSTALL_FAILED'] = 'The maintenance-mode setting could not be created.';
$MOD_MAINTAINANCE['UNINSTALL_FAILED'] = 'The maintenance-mode setting could not be removed.';
