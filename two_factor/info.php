<?php
$module_directory='two_factor';
$module_uuid = 'a926f864-56af-4713-a2a3-95f2a81f24c0';
$metadataLanguage=defined('LANGUAGE')?strtoupper(substr((string)LANGUAGE,0,2)):'EN';
$metadataFile=__DIR__.'/languages/'.preg_replace('/[^A-Z]/','',$metadataLanguage).'.php';
if(!is_file($metadataFile))$metadataFile=__DIR__.'/languages/EN.php';
$metadata=(array)require $metadataFile;
$module_name=(string)($metadata['admin_title']??'2FA Authentication');
$module_function='tool,initialize';
$module_version='1.1.40';
$module_platform='1.7.0';
$module_author='Mathias Lange';
$module_license='GNU GPL2 or later';
$module_description=(string)($metadata['admin_intro']??'Extensible 2FA platform for installable authentication providers.');
$module_icon='fa fa-shield';
$module_requires_any='WBCE>=1.7.0|wbce_hook_bridge>=1.2.0';
$module_requires_php='8.2.0';
