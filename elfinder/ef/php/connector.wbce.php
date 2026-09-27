<?php

require_once '../../../../config.php';
$elfinderLanguage = defined('LANGUAGE') ? strtoupper(substr((string) LANGUAGE, 0, 2)) : 'EN';
$elfinderLanguageFile = dirname(__DIR__, 2) . '/languages/' . preg_replace('/[^A-Z]/', '', $elfinderLanguage) . '.php';
if (!is_file($elfinderLanguageFile)) $elfinderLanguageFile = dirname(__DIR__, 2) . '/languages/EN.php';
require $elfinderLanguageFile;

/**
 * @file /modules/elfinder/connector.wbce_cke.php
 * @brief The WBCE connector for connecting whith elFinder
 * @important The including script needs to take care of a granular access controll!
 */

// no direct file access
if (count(get_included_files()) == 1) {
    header("Location: ../index.php", true, 301);
    exit;
}

// Make sure we include from wbce
if (!defined('WB_PATH')) {
    header("Location: ../index.php", true, 301);
    exit;
}

// Define Fileendings we dont want to see (the files , not just the endings)
$wbceForbiddenExtensions = array_filter(array_map('trim', explode(',', (string)RENAME_FILES_ON_UPLOAD)));
$wbceForbiddenExtensions = array_map(static function ($extension) { return preg_quote(ltrim($extension, '.'), '/'); }, $wbceForbiddenExtensions);
$sForbiddenRegex = $wbceForbiddenExtensions ? '/\.('.implode('|', $wbceForbiddenExtensions).')$/i' : '/(?!)^/';

// In wbce we have our own setting for Error reporting
// error_reporting(E_ALL); // Set E_ALL for debuging

// load composer autoload before load elFinder autoload If you need composer
// No composer in wbce
// require './vendor/autoload.php';

// elFinder autoload
require 'autoload.php';

/**
 * @brief Simple function to prevent uploads forbidden by WBCEs  RENAME_FILES_ON_UPLOAD constant.
 * @param string $sFileName When the connector calls this function and hands the filename to it.
 * @return boolean true if filename is OK  and false if not.
 */
function wbce_filenames_ok($sFileName)
{

    // Convert WBCE Setting to regex
    $extensions = array_filter(array_map('trim', explode(',', (string)RENAME_FILES_ON_UPLOAD)));
    $sForbidden = implode('|', array_map(static function ($extension) { return preg_quote(ltrim($extension, '.'), '/'); }, $extensions));

    // check forbidden list
    if ($sForbidden !== '' && preg_match("/\.($sForbidden)$/i", basename((string)$sFileName))) {
        return false;
    }

    // check if file contains at least a file extension .***
    if (preg_match('/^[^\.].*$/', $sFileName)) {
        return true;
    }

    return false;
}

/**
 * @brief Simple function to demonstrate how to control file access using "accessControl" callback.
 * This method will disable accessing files/folders starting from '.' (dot)
 * Good idea we keep this in WBCE.
 * @param string $attr attribute name (read|write|locked|hidden)
 * @param string $path absolute file path
 * @param string $data value of volume option `accessControlData`
 * @param object $volume elFinder volume driver object
 * @param bool|null $isDir path is directory (true: directory, false: file, null: unknown)
 * @param string $relpath file path relative to volume root directory started with directory separator
 * @return bool|null
 */
function access($attr, $path, $data, $volume, $isDir, $relpath)
{
    $basename = basename($path);
    return $basename[0] === '.' // if file/folder begins with '.' (dot)
    && strlen($relpath) !== 1 // but with out volume root
        ? !($attr == 'read' || $attr == 'write') // set read+write to false, other (locked+hidden) set to true
        : null; // else elFinder decide it itself
}

function wbce_media_command_before($cmd, &$args, $elfinder, $volume)
{
    $event = $cmd === 'rm' ? 'media.beforeDelete' : ($cmd === 'rename' ? 'media.beforeRename' : 'media.beforeUpload');
    if (function_exists('wbce_hook_allows') && !wbce_hook_allows($event, array('command' => $cmd, 'arguments' => $args))) {
        return array('preventexec' => true, 'results' => array('error' => $GLOBALS['ELFINDER']['HOOK_REJECTED']));
    }
    return null;
}

function wbce_media_upload_presave(&$target, &$name, $temporaryFile, $elfinder, $volume)
{
    $validation = function_exists('wbce_apply_array_filters') ? wbce_apply_array_filters('media.upload.validation', array(
        'valid' => true,
        'name' => $name,
        'temporary_file' => $temporaryFile,
        'target' => $target,
    )) : array('valid'=>true,'name'=>$name,'temporary_file'=>$temporaryFile,'target'=>$target);
    if (isset($validation['name'])) {
        $name = basename($validation['name']);
    }
    return !empty($validation['valid']) ? null : array('error' => isset($validation['message']) ? $validation['message'] : $GLOBALS['ELFINDER']['UPLOAD_REJECTED']);
}

function wbce_media_command_after($cmd, &$result, $args, $elfinder, $volume)
{
    $events = array('upload' => 'media.uploaded', 'rm' => 'media.deleted', 'rename' => 'media.renamed');
    if (isset($events[$cmd]) && empty($result['error'])) {
        if (function_exists('wbce_do_action')) {
            wbce_do_action($events[$cmd], $result, $args);
            wbce_do_action('cache.invalidate', array('scope' => 'media', 'command' => $cmd));
        }
    }
    return false;
}

$admin = new admin('Media', 'media_view', false, false);
if (($admin->get_permission('media_view') === true)) {

    $wbceHomeFolder = trim(str_replace('\\', '/', (string)($_SESSION['HOME_FOLDER'] ?? '')), '/');
    if ($wbceHomeFolder !== '' && (strpos($wbceHomeFolder, "\0") !== false || preg_match('~(^|/)\.\.(/|$)~', $wbceHomeFolder))) {
        http_response_code(403);
        exit;
    }
    if ($wbceHomeFolder !== '') {
        $wbceMediaRoot = realpath(WB_PATH . MEDIA_DIRECTORY);
        $wbceHomeRoot = realpath(WB_PATH . MEDIA_DIRECTORY . '/' . $wbceHomeFolder);
        $wbceMediaPrefix = $wbceMediaRoot === false ? '' : rtrim(str_replace('\\', '/', $wbceMediaRoot), '/') . '/';
        if ($wbceHomeRoot === false || $wbceMediaPrefix === '' || strpos(str_replace('\\', '/', $wbceHomeRoot) . '/', $wbceMediaPrefix) !== 0) {
            http_response_code(403);
            exit;
        }
    }

    // initialize vars for 'disabled' array. By default, the options are allowed, so set empty strings.
    $noup = '';
    $norn = '';
    $norm = '';
    $nomk = '';
    $nopa = '';
    $noco = '';
    $nodu = '';
    $noex = '';
    $nore = '';

    // if user is not allowed to upload files, file operations and image resize is not allowed either.
    if (($admin->get_permission('media_upload') === false)) {
        $noup = 'upload';
        $nopa = 'paste';
        $noco = 'copy';
        $nodu = 'duplicate';
        $noex = 'extract';
        $nore = 'resize';
    }

    // if needed, disallow file renaming, directory deleting and directory creating.
    if (($admin->get_permission('media_rename') === false)) {
        $norn = 'rename';
    }
    if (($admin->get_permission('media_delete') === false)) {
        $norm = 'rm';
    }
    if (($admin->get_permission('media_create') === false)) {
        $nomk = 'mkdir';
    }

    // Set the parameters for connecting to elFinder
    if ($wbceHomeFolder === '') {

        // Documentation for connector options:
        // https://github.com/Studio-42/elFinder/wiki/Connector-configuration-options
        $opts = array(
            'debug' => false,
            'roots' => array(
                // Items volume
                array(
                    'driver' => 'LocalFileSystem', // driver for accessing file system (REQUIRED)
                    'path' => WB_PATH . MEDIA_DIRECTORY.'/', // path to files (REQUIRED)
                    'URL' => WB_URL . MEDIA_DIRECTORY.'/', // URL to files (REQUIRED)
                    'quarantine' => WB_PATH . '/var/modules/elfinder/.quarantine', // Temporary directory for archive file extracting.
                    'tmbPath' => WB_PATH . '/var/modules/elfinder/.tmb', // Tumbnail Directory
                    'tmbURL' => WB_URL . '/var/modules/elfinder/.tmb', // Tumbnail Directory
                    'winHashFix' => DIRECTORY_SEPARATOR !== '/', // to make hash same to Linux one on windows too
                    'accessControl' => 'access', // disable and hide dot starting files (OPTIONAL) see "access" function above
                    'acceptedName' => 'wbce_filenames_ok', // call this function to check filename

                    'attributes' => array(
                        array(
                            'pattern' => $sForbiddenRegex, // You can also set permissions for file types by adding, for example, .jpg inside pattern.
                            'read' => false,
                            'write' => false,
                            'locked' => true,
                            'hidden' => true
                        )
                    ),

                    'disabled' => array( // Set default and optional values for disabled options.
                        'hide',
                        'empty',
                        'netmount',
                        'help',
                        'preference',
                        'mkfile',
                        'edit',
                        $noup,
                        $norn,
                        $norm,
                        $nomk,
                        $nopa,
                        $noco,
                        $nodu,
                        $noex,
                        $nore
                    )
                )
            )
        );
    } else {
        $opts = array(
            'debug' => false,
            'roots' => array(

                array(
                    'alias' => "Home (" . $wbceHomeFolder . ")",
                    'driver' => 'LocalFileSystem',
                    'path' => WB_PATH . MEDIA_DIRECTORY. '/' . $wbceHomeFolder,
                    'URL' => WB_URL . MEDIA_DIRECTORY. '/' . implode('/', array_map('rawurlencode', explode('/', $wbceHomeFolder))),
                    'quarantine' => WB_PATH . '/var/modules/elfinder/.quarantine',
                    'tmbPath' => WB_PATH . '/var/modules/elfinder/.tmb',
                    'tmbURL' => WB_URL . '/var/modules/elfinder/.tmb',
                    'winHashFix' => DIRECTORY_SEPARATOR !== '/',
                    'accessControl' => 'access',
                    'acceptedName' => 'wbce_filenames_ok',

                    'attributes' => array(
                        array(
                            'pattern' => $sForbiddenRegex,
                            'read' => false,
                            'write' => false,
                            'locked' => true,
                            'hidden' => true
                        )
                    ),

                    'disabled' => array( // Set default and optional values for disabled options.
                        'hide',
                        'empty',
                        'netmount',
                        'help',
                        'preference',
                        'mkfile',
                        'edit',
                        $noup,
                        $norn,
                        $norm,
                        $nomk,
                        $nopa,
                        $noco,
                        $nodu,
                        $noex,
                        $nore
                    )
                )
            )
        );
    }
} else {
    die;
}

$opts['bind'] = array(
    'upload.pre rm.pre rename.pre' => array('wbce_media_command_before'),
    'upload.presave' => array('wbce_media_upload_presave'),
    'upload rm rename' => array('wbce_media_command_after'),
);

// run elFinder
$connector = new elFinderConnector(new elFinder($opts));
$connector->run();
