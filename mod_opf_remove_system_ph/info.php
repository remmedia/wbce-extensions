<?php
/**
 * WBCE CMS
 * Way Better Content Editing.
 * Visit https://wbce.org to learn more and to join the community.
 *
 * @copyright       WBCE Project (2015-)
 * @category        opffilter
 * @package         OPF Remove System PH
 * @version         1.1.8
 * @authors         Martin Hecht (mrbaseman)
 * @link            https://forum.wbce.org/viewtopic.php?id=176
 * @license         GNU GPL2 (or any later version)
 * @platform        WBCE 1.7.0 (compatible with 1.6.8)
 * @requirements    OutputFilter Dashboard 1.6.15 and PHP 8.2 or higher
 *
 **/

/*
 *      CHANGELOG
 *
 *		1.1.7	2023-02-25		- update description (florian)
 *      1.1.6   2020-05-06      - only remove empty lines resulting from replace
 *      1.1.5   2019-07-05      - by default enable filter on searchresults
 *      1.1.4   2019-04-22      - include opf functions in upgrade script
 *      1.1.3   2019-03-28      - make description more meaningful
 *      1.1.2   2019-03-09      - bugfix in install/upgrade
 *      1.1.1   2019-03-07      - reorder filters into new categories
 *      1.1.0   2018-11-05      - remove any empty lines in the content
 *      1.0.1   2018-10-07      - during installation switch filter on by default
 *      1.0.0   2018-09-12      - turn classical outputfilter to an OpF filter module
 *
 */


/* -------------------------------------------------------- */
// Must include code to stop this file being accessed directly
if(!defined('WB_PATH')) {
        // Stop this file being access directly
        if(!headers_sent()) header("Location: ../index.php",TRUE,301);
        die('<head><title>Access denied</title></head><body><h2 style="color:red;margin:3em auto;text-align:center;">Cannot access this file directly</h2></body></html>');
}
/* -------------------------------------------------------- */


$module_directory       = 'mod_opf_remove_system_ph';
$module_uuid = '74451f83-ce41-4631-9d6d-cc20ec170813';
$module_name            = 'OPF Remove System PH';
$module_function        = 'opffilter';
$module_version         = '1.1.11';
$module_platform        = '1.7.0';
$module_requires_php    = '8.2.0';
$module_author          = 'Martin Hecht (mrbaseman)';
$module_license         = 'GNU GPL2 (or any later version)';
$metadataLanguage=defined('LANGUAGE')?strtoupper((string)LANGUAGE):'EN';
$metadataFile=__DIR__.'/languages/metadata/'.$metadataLanguage.'.php';
if(!is_readable($metadataFile))$metadataFile=__DIR__.'/languages/metadata/EN.php';
if(is_readable($metadataFile))require $metadataFile;
$module_description     = isset($opfRemovePhMetadataDescription)?$opfRemovePhMetadataDescription:'Removes remaining WBCE system placeholders from generated pages';
$module_level           = 'core';
$module_dependencies    = 'outputfilter_dashboard>=1.6.15';
$module_requires_any    = 'WBCE>=1.6.8';
