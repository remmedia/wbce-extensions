<?php
$module_directory = 'two_factor_totp';
$module_uuid = '06f86b50-6daa-400e-96f2-00ca510bbf55';
$module_name = '2FA – Authenticator-App (TOTP)';
$module_function = 'initialize';
$module_version='2.1.21';
$module_platform = '1.7.0';
$module_author = 'Mathias Lange';
$module_license = 'GNU GPL2 or later';
$module_description = 'Ermöglicht zeitbasierte Einmalcodes nach RFC 6238 mit gängigen Authenticator-Apps.';
$module_icon = 'fa fa-shield';
$module_requires_any = 'WBCE>=1.7.0|wbce_hook_bridge>=1.2.0';
$module_dependencies = 'two_factor>=1.1.32';
$module_requires_php = '8.2.0';
