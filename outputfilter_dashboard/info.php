<?php
/**
 *
 * @category        tool
 * @package         Outputfilter Dashboard
 * @version         1.6.5
 * @authors         Thomas "thorn" Hornik <thorn@nettest.thekk.de>, Christian M. Stefan (Stefek) <stefek@designthings.de>, Martin Hecht (mrbaseman) <mrbaseman@gmx.de>
 * @copyright       (c) 2009,2010 Thomas "thorn" Hornik, 2010-2023 Christian M. Stefan (Stefek), 2016-2023 Martin Hecht (mrbaseman)
 * @link            https://github.com/mrbaseman/outputfilter_dashboard
 * @link            https://addons.wbce.org/pages/addons.php?do=item&item=53
 * @link            https://forum.wbce.org/viewtopic.php?id=176
 * @license         GNU General Public License, Version 3
 * @platform        WBCE 1.x
 * @requirements    PHP 7.4 - 8.2
 *
 * This file is part of OutputFilter-Dashboard, a module for WBCE and Website Baker CMS.
 *
 * OutputFilter-Dashboard is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * OutputFilter-Dashboard is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with OutputFilter-Dashboard. If not, see <http://www.gnu.org/licenses/>.
 *
 **/

// see the file CHANGELOG for details about the history of this module

$module_directory   = 'outputfilter_dashboard';
$module_uuid = 'cb6ef0b1-888f-48e2-8b2f-da07da756b36';
$module_name        = 'Output Filter Dashboard';
$module_function    = 'tool';
$module_version='1.6.23';
$module_platform    = '1.7.0';
$module_requires_any = 'WBCE>=1.6.8';
$module_requires_php = '8.2.0';
$module_author      = 'Thomas "thorn" Hornik <thorn@nettest.thekk.de>, Christian M. Stefan (Stefek) <stefek@designthings.de>, Martin Hecht (mrbaseman) <mrbaseman@gmx.de>';
$module_license     = 'GNU General Public License, Version 3';
$metadataLanguage = defined('LANGUAGE') ? strtoupper((string) LANGUAGE) : 'EN';
$metadataFile = __DIR__ . '/languages/metadata/' . $metadataLanguage . '.php';
if (!is_readable($metadataFile)) {
    $metadataFile = __DIR__ . '/languages/metadata/EN.php';
}
if (is_readable($metadataFile)) {
    require $metadataFile;
}
$module_description = isset($outputfilter_dashboard_metadata_description)
    ? $outputfilter_dashboard_metadata_description
    : 'Admin tool for managing output filters';
$module_icon        = 'fa fa-magic';
$module_level       = 'core';
