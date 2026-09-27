<?php
/**
 * WBCE CMS
 * Way Better Content Editing.
 * Visit https://wbce.org to learn more and to join the community.
 *
 * @copyright       WBCE Project (2015-)
 * @category        opffilter
 * @package         OPF Replace Contents
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
 *		1.0.8	2023-02-25		- rename, update description (florian)
 *      1.0.7   2019-07-05      - by default enable filter on searchresults
 *      1.0.6   2019-04-22      - include opf functions in upgrade script
 *      1.0.5   2019-04-22      - move up before sys rel and short url filter
 *      1.0.4   2019-03-28      - make description more meaningful
 *      1.0.3   2019-03-09      - bugfix in install/upgrade
 *      1.0.2   2019-03-07      - reorder filters into new categories
 *      1.0.1   1018-10-07      - during installation switch filter on by default
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


$module_directory       = 'mod_opf_replace_stuff';
$module_uuid = 'b9a8abef-bc13-40af-a475-0021ca05ea22';
$module_name            = 'OPF Replace Contents';
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
$module_description     = isset($opfReplaceStuffMetadataDescription)?$opfReplaceStuffMetadataDescription:'Replaces placeholder areas with matching marked content';
$module_level           = 'core';
$module_dependencies    = 'outputfilter_dashboard>=1.6.15';
$module_requires_any    = 'WBCE>=1.6.8';
