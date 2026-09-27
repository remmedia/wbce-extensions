<?php
/**
 * AdminTool: addonMonitor
 *
 * This file provides some functions for the addonMonitor Tool.
 *
 * @package     addonMonitor
 * @author      Christian M. Stefan (Stefek)
 * @copyright   Christian M. Stefan
 * @license     http://www.gnu.org/licenses/gpl-2.0.html
 */

// prevent this file from being accessed directly:
defined('WB_PATH') or die("Cannot access this file directly");

if (!function_exists('addon_monitor_db_value')) {
    function addon_monitor_db_value($database, $sql)
    {
        // WBCE 1.7 uses fetchValue(); get_one() remains isolated for 1.6.8.
        if (!is_object($database)) {
            return null;
        }
        if (is_callable(array($database, 'fetchValue'))) {
            return $database->fetchValue($sql);
        }
        return is_callable(array($database, 'get_one')) ? $database->get_one($sql) : null;
    }
}

if (!function_exists('addon_monitor_query')) {
    function addon_monitor_query($database, $sql)
    {
        if (!is_object($database) || !is_callable(array($database, 'query'))) return false;
        $result = $database->query($sql);
        if ($result === false || $result === null || !is_object($result) || !is_callable(array($result, 'fetchRow'))) return false;
        if (is_callable(array($result, 'error'))) {
            $error = $result->error();
            if (is_string($error) && $error !== '') return false;
        }
        return $result;
    }
}

if (!function_exists('addon_monitor_safe_directory')) {
    function addon_monitor_safe_directory($directory)
    {
        $directory = (string)$directory;
        return preg_match('/^[a-z0-9][a-z0-9_-]{0,79}$/i', $directory) ? $directory : '';
    }
}

if (!function_exists('getModulesArray')) {
    function getModulesArray()
{
	$whiteListAddons = ['page', 'tool', 'snippet', 'wysiwyg'];
    global $database;
    $aAddons['addons'] = array();
    $aAddons['count_tools'] = 0;
    $aAddons['count_snippets'] = 0;
    $aAddons['count_pagemodules'] = 0;
    $aAddons['count_wysiwygeditors'] = 0;
    $aAddons['default_wysiwyg'] = addon_monitor_db_value($database, "SELECT `value` FROM `{TP}settings` WHERE `name` = 'wysiwyg_editor'");
    
    $hasActivation = is_object($database)
        && is_callable(array($database, 'field_exists'))
        && $database->field_exists('{TP}addons', 'active');
    $sQueryAddons = (
        "SELECT DISTINCT a.*,
        " . ($hasActivation ? "a.`active`" : "1") . " AS core_enabled,
        IF(s.module IS NOT NULL, 'Y', 'N') AS usage_active
        FROM {TP}addons a
        LEFT JOIN {TP}sections s ON a.directory = s.module
        WHERE `type` = 'module'"
    );

    if ($oAddons = addon_monitor_query($database, $sQueryAddons)) {
        // Loop through addons
        while ($aRec = $oAddons->fetchRow(MYSQLI_ASSOC)) {
            $aRec['directory'] = addon_monitor_safe_directory($aRec['directory'] ?? '');
            if ($aRec['directory'] === '') continue;
            // Split the function string into an array
            $functions = array_map('trim', explode(',', $aRec['function']));
            foreach ($functions as $function) {
				if (!in_array($function, $whiteListAddons)) continue;
				$aRec['function'] = $function;
				#debug_dump($function, $aRec['name']);
                switch ($function) {
                    case 'page':
                        ++$aAddons['count_pagemodules'];
                        if ($aRec['usage_active'] == 'Y') {
                            $sQueryActiveSections = ("SELECT `section_id`, `page_id` FROM `{TP}sections` WHERE `module` = '" . $aRec['directory'] . "'");
                            if ($oActiveSections = addon_monitor_query($database, $sQueryActiveSections)) {
                                while ($aSections = $oActiveSections->fetchRow(MYSQLI_ASSOC)) {
                                    $aRec['active_sections'][(int)$aSections['section_id']] = (int)$aSections['page_id'];
                                }
                            }
                        }
                        break;

                    case 'tool':
                        $aAddons['count_tools']++;
                        $sIconFile = WB_PATH . "/modules/" . $aRec['directory'] . "/tool_icon.png";
                        $aRec['icon'] = is_readable($sIconFile) ? "../../modules/" . $aRec['directory'] . "/tool_icon.png" : "../../modules/" . basename(dirname(__FILE__)) . "/icons/tool.png";
                        break;

                    case 'snippet':
                        ++$aAddons['count_snippets'];
                        break;

                    case 'wysiwyg':
                        ++$aAddons['count_wysiwygeditors'];
                        break;
                }

                // Handle icons for snippets and page modules
                if ($function === 'snippet' || $function === 'page') {
                    $sIconFile = "/modules/" . $aRec['directory'] . "/addon_icon.png";
                    $aRec['icon'] = is_readable(WB_PATH . $sIconFile) ? "../.." . $sIconFile : "../../modules/" . basename(dirname(__FILE__)) . "/icons/" . ($function === 'snippet' ? 'snippet' : 'page_module') . ".png";
                }

				$aAddons['addons'][] = $aRec;
            }
        }
    }
    
    return $aAddons;
}
}

if (!function_exists('getTemplatesArray')) {
    function getTemplatesArray()
    {
        global $database;
        $sDefaultAcp = addon_monitor_db_value($database, "SELECT `value` FROM `{TP}settings` WHERE `name` = 'default_theme'");
        $aAddons['addons'] = array();
        $aAddons['count_pagetemplates'] = 0;
        $aAddons['count_acpthemes'] = 0;
        $sQueryAddons = (
            "SELECT DISTINCT a . * ,
            IF( p.template IS NOT NULL , 'Y', 'N' ) AS active
            FROM {TP}addons a
                LEFT JOIN {TP}pages p
                ON a.directory = p.template
            WHERE `function` = 'theme' OR `function` = 'template'
            ORDER BY `function`, `name`"
        );

        if ($oAddons = addon_monitor_query($database, $sQueryAddons)) {
            // Loop through addons
            while ($aRec = $oAddons->fetchRow(MYSQLI_ASSOC)) {
                $aRec['directory'] = addon_monitor_safe_directory($aRec['directory'] ?? '');
                if ($aRec['directory'] === '') continue;
                // grab for page_id's Addon is used on different pages
                if ($aRec['function'] == 'template') {
                    ++$aAddons['count_pagetemplates'];
                    // grab for page_id's if Addon is used on different pages
                    if ($aRec['active'] == 'Y') {
                        $sQueryActiveSections = ("SELECT `page_id` FROM `{TP}pages` WHERE `template` = '".$aRec['directory']."'");
                        if ($oActiveSections = addon_monitor_query($database, $sQueryActiveSections)) {
                            while ($aSections = $oActiveSections->fetchRow(MYSQLI_ASSOC)) {
                                $aRec['active_pages'][] = (int)$aSections['page_id'];
                            }
                        }
                    }
                } else {
                    ++$aAddons['count_acpthemes'];
                }
                // icon
                $sIconFile = "/templates/".$aRec['directory']."/preview.jpg";
                if (is_readable(WB_PATH.$sIconFile)) {
                    $aRec['icon'] = "../..".$sIconFile;
                } else {
                    $sType = ($aRec['function'] == 'theme') ? 'acp_theme' : 'page_template';
                    $aRec['icon'] = "../../modules/".basename(dirname(__FILE__))."/icons/".$sType."_preview.jpg";
                }
                $aAddons['addons'][]  = $aRec;
            }
        }
        return $aAddons;
    }
}
if (!function_exists('getLanguagesArray')) {
    function getLanguagesArray()
    {
        global $database;
        $aAddons['addons'] = array();
        $sQueryAddons = (
            "SELECT DISTINCT a . * ,
            IF( p.language IS NOT NULL , 'Y', 'N' ) AS active
            FROM {TP}addons a
                LEFT JOIN {TP}pages p
                ON a.directory = p.language
            WHERE type = 'language'"
        );

        if ($oAddons = addon_monitor_query($database, $sQueryAddons)) {
            // Loop through addons
            while ($aRec = $oAddons->fetchRow(MYSQLI_ASSOC)) {
                $aRec['directory'] = strtoupper(addon_monitor_safe_directory($aRec['directory'] ?? ''));
                if ($aRec['directory'] === '' || !preg_match('/^[A-Z]{2}$/', $aRec['directory'])) continue;
                // grab for page_id's if Addon is used on different pages
                if ($aRec['active'] == 'Y') {
                    $sQueryActiveSections = ("SELECT `page_id` FROM `{TP}pages` WHERE `language` = '".$aRec['directory']."'");
                    if ($oActiveSections = addon_monitor_query($database, $sQueryActiveSections)) {
                        while ($aSections = $oActiveSections->fetchRow(MYSQLI_ASSOC)) {
                            $aRec['active_pages'][] = (int)$aSections['page_id'];
                        }
                    }
                }
                // icon
                $sIconFile = "/languages/".strtolower($aRec['directory']).".png";
                if (is_readable(WB_PATH.$sIconFile)) {
                    $aRec['icon'] = "../..".$sIconFile;
                } else {
                    $sType = ($aRec['function'] == 'theme') ? 'acp_theme' : 'frontend_template';
                    $aRec['icon'] = "../../modules/".basename(dirname(__FILE__))."/icons/unknown.png";
                }
                $aAddons['addons'][]  = $aRec;
            }
        }
        return $aAddons;
    }
}
