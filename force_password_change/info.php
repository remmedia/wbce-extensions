<?php
$module_directory = 'force_password_change';
$module_uuid = 'ccd3c967-a3cf-4d10-82ce-0053353b1551';
$module_name = 'Passwortänderung erzwingen';
$module_function = 'preinit,initialize';
$module_version='2.0.18';
$module_platform = '1.7.0';
$module_author = 'Mathias Lange';
$module_license = 'GNU GPL2 or later';
$module_description = 'Erzwingt für ausgewählte Benutzer eine Passwortänderung bei der nächsten Anmeldung. Die Vorgabe wird direkt in der Benutzerverwaltung gesetzt. Benötigt WBCE 1.7.0 oder die Hook-Bridge ab Version 1.2.0.';
$module_icon = 'fa fa-key';
$module_requires_any = 'WBCE>=1.7.0|wbce_hook_bridge>=1.2.0';
$module_requires_php = '8.2.0';
