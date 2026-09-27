<?php
$module_directory='security_center';
$module_uuid = '65a5e1cb-8c2e-4f25-9a38-9807325b4391';
$metadataLanguage=defined('LANGUAGE')?strtoupper(substr((string)LANGUAGE,0,2)):'DE';
$metadataFile=__DIR__.'/languages/metadata/'.$metadataLanguage.'.php';
if(!is_readable($metadataFile))$metadataFile=__DIR__.'/languages/metadata/EN.php';
if(is_readable($metadataFile))require $metadataFile;
$module_name='Security Center';
$module_function='tool,preinit,initialize';
$module_version='1.0.92';
$module_platform='1.7.0';
$module_author='Mathias Lange';
$module_license='GNU GPL2 or later';
$module_description='Schützt WBCE lokal mit Firewall, Angriffserkennung, Sperrlisten, Überwachung, Sicherheitsauswertung, Integritätsprüfung, Virenscanner und Codescanner.';
$module_icon='fa fa-shield';
$module_dependencies='worker>=1.10.11';
$module_requires_any='WBCE>=1.7.0|wbce_hook_bridge>=1.2.0';
$module_requires_php='8.2.0';
