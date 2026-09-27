<?php
$module_directory = 'accessibility_tools';
$module_uuid = 'd19eabe2-ecd1-49a3-8b10-ce1deecb7cac';
require_once __DIR__ . '/Language.php';
$accessibilityToolsInfo = accessibility_tools_texts();
$module_name = 'Accessibility Tools';
$module_function = 'tool,preinit,initialize,snippet';
$module_version = '1.3.22';
$module_platform = '1.7.0';
$module_author = 'Mathias Lange';
$module_license = 'GNU GPL2 or later';
$module_description = 'Bietet global konfigurierbare und sprachabhängige Bedienungshilfen für WBCE-Frontendseiten.';
$module_icon = 'fa fa-universal-access';
$module_requires_php = '8.2.0';
$module_requires_any = 'WBCE>=1.6.8';
