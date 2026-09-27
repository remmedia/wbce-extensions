<?php
$module_directory='two_factor_webauthn';
$module_uuid = '8b962d7f-f9ae-4f01-9368-dbb1c58f9a1b';
$module_name='2FA – Passkeys und Sicherheitsschlüssel';
$module_function='initialize';
$module_version='1.1.20';
$module_platform='1.7.0';
$module_author='Mathias Lange';
$module_license='GNU GPL2 or later';
$module_description='Ermöglicht eine zusätzliche Anmeldeprüfung mit Passkeys und FIDO2-kompatiblen Sicherheitsschlüsseln über WebAuthn.';
$module_icon='fa fa-key';
$module_dependencies='two_factor>=1.1.33';
$module_requires_any='WBCE>=1.7.0|wbce_hook_bridge>=1.2.0';
$module_requires_php='8.2.0';
