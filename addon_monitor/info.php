<?php
$module_directory 		= 'addon_monitor';
$module_uuid = '39c89d18-3d6b-44c5-8cf4-60df0a58d9f5';
require_once __DIR__.'/Language.php';
$module_name 			= 'Addon Monitor';
$module_function 		= 'tool';
$module_version         = '1.1.13';
$module_status          = 'stable';
$module_platform 		= '1.7.0';
$module_requires_any   = 'WBCE>=1.6.8';
$module_author 			= 'Christian M. Stefan (Stefek)';
$module_license 		= 'GNU/GPL v.2';
$module_description 	= 'Zeigt installierte Erweiterungen, deren Status, Verwendung und Abhängigkeiten übersichtlich an.';
$module_requires_php    = '8.2.0';

/**
 * Version history
 *
 * 1.1.12 - Restrict filesystem identifiers, avoid executing foreign language files and harden query handling.
 *
 * 1.1.11 - Escape Twig output locally and harden database, JSON and external-link handling.
 *
 * 1.1.10 - Use a clean asynchronous endpoint and move runtime text out of inline JavaScript.
 *
 * 1.1.9 - Confirm registered modules, repair follow-up state and renew renamed FTAN fields.
 *
 * 1.1.8 - Harden asynchronous state changes and keep card labels language-driven.
 *
 * 1.1.6 - WBCE 1.7 activation, Twig 1.6.8 fallback and theme-safe async controls.
 *
 * 1.1.5 - Lightweight async activation and fully localized card labels.
 *
 * 1.1.4 - Theme/language cleanup and safe production Twig rendering.
 *
 * 1.1.2 - Remove unscoped legacy backend CSS and repair template table markup.
 *
 * 1.1.0 - WBCE 1.7 primary compatibility with 1.6.8 fallback.
 *
 * 0.8.2 - Prevent wrapped usage headings and values in addon cards.
 *
 * 0.8.1 - Store-style addon cards, stronger list hierarchy and responsive details.
 *
 * 0.8.0 - Activation switches without uninstalling files or data.
 *
 * 0.7.3 - Modern card and table layout aligned with the Store interface.
 *
 * 0.7.2 - fix for hybrid modules
 *
 * 0.7.1 - cs fixed files
 *
 * 0.7.0 - fix issues changed twig version and PHP 8
 *
 * 0.6.8 - add missing inactive icon (thanks to kleo)
 *
 * 0.6.7 - idk
 *
 * 0.6.6 - fix issue with warnings when OfA is installed (thanks to freesbee)
 *
 * 0.6.5 - fix issue with empty lists on PHP 7.4 / MySQL 8
 *
 * 0.6.4 - corrects the path to the flag icons
 *
 */
