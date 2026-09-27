<?php
/**
 * WBCE CMS
 * Way Better Content Editing.
 * Visit https://wbce.org to learn more and to join the community.
 *
 * @copyright       WBCE Project (2015-)
 * @category        opffilter
 * @package         OPF Insert
 * @version         1.0.9
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
 *		1.0.8	2023-02-25		- update description (florian)
 *      1.0.7   2019-07-05      - by default enable filter on searchresults
 *      1.0.6   2019-04-22      - include opf functions in upgrade script
 *      1.0.5   2019-03-26      - update requirements
 *      1.0.4   2019-03-22      - change filter type to page (last)
 *      1.0.3   2019-03-09      - bugfix in install/upgrade
 *      1.0.2   2019-03-07      - reorder filters into new categories
 *      1.0.1   2017-04-11      - make install/upgrade work w/o classical output_filters
 *      1.0.0   2017-01-23      - turn classical outputfilter to an OpF filter module
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


$module_directory       = 'mod_opf_insert';
$module_uuid = '98d28c9f-9016-494e-b7e8-704c7efc1064';
$module_name            = 'OPF Insert';
$module_function        = 'opffilter';
$module_version         = '1.0.12';
$module_platform        = '1.7.0';
$module_requires_php    = '8.2.0';
$module_author          = 'Martin Hecht (mrbaseman)';
$module_license         = 'GNU GPL2 (or any later version)';
$metadataLanguage=defined('LANGUAGE')?strtoupper((string)LANGUAGE):'EN';
$metadataFile=__DIR__.'/languages/metadata/'.$metadataLanguage.'.php';
if(!is_readable($metadataFile))$metadataFile=__DIR__.'/languages/metadata/EN.php';
if(is_readable($metadataFile))require $metadataFile;
$module_description     = isset($opfInsertMetadataDescription)?$opfInsertMetadataDescription:'Fills placeholders for JavaScript, CSS, metadata and page titles';
$module_level           = 'core';
$module_dependencies    = 'outputfilter_dashboard>=1.6.15';
$module_requires_any    = 'WBCE>=1.6.8';
