<?php
/**
 * WBCE CMS
 * Way Better Content Editing.
 * Visit https://wbce.org to learn more and to join the community.
 *
 * @copyright Ryan Djurovich (2004-2009)
 * @copyright WebsiteBaker Org. e.V. (2009-2015)
 * @copyright WBCE Project (2015-)
 * @license GNU GPL2 (or any later version)
 */

$core = true;

$module_directory   = 'captcha_control';
$module_uuid = 'aef6fe8b-7d20-4117-a3cf-6c221d82bd3a';
$module_name        = 'CAPTCHA - Spam-Schutz';
$module_function    = 'tool,initialize';
$module_version='3.1.55';
$module_platform    = '1.7.0';
$module_author      = 'Mathias Lange';
$module_license     = 'GNU GPL2';
$module_description = 'Verwaltet den CAPTCHA- und Spam-Schutz zentral und bindet installierbare lokale sowie externe CAPTCHA-Provider in WBCE-Formulare ein.';
$module_icon        = 'fa fa-shield';
$module_dependencies = '';
$module_requires_any = 'WBCE>=1.7.0|wbce_hook_bridge>=1.2.0';
$module_requires_php = '8.2.0';

/**
 * Version history
 *
 * 3.1.55 - Saves through the headless Admin-Tools route so the JSON response is not wrapped in backend HTML.
 *
 * 3.1.54 - Restores the FTAN in FormData when output filters strip hidden input values.
 *
 * 3.1.53 - Supplies the mandatory FTAN token to the settings form so saving is authorised.
 *
 * 3.1.52 - Explicit asynchronous request marker works even when a server removes AJAX headers.
 *
 * 3.1.51 - Asynchronous saving always returns clean JSON, even when a provider emits notices or output.
 *
 * 3.1.36 - Interpret persisted Boolean constants reliably and harden settings cleanup.
 *
 * 3.1.35 - Robust hook-bridge loading, legacy database compatibility, escaped Twig output and reliable JSON responses.
 *
 * 3.1.34 - WBCE 1.7 metadata, fully localized fallback preview and theme-owned admin styling.
 *
 * 3.1.27 - ALTCHA-Prüffeld bleibt im Grundzustand templateunabhängig weiß.
 *
 * 3.1.26 - ALTCHA-Logo in allen globalen Einbindungen auf eine feste kompakte Größe begrenzt.
 *
 * 3.1.25 - WBCE-1.6.8-Fallback ohne vorausgesetztes loadPlugin() und TXEXT-Global.
 *
 * 3.1.24 - Native Farbfelder und robuste Live-Vorschau als Fallback für Coloris.
 *
 * 3.1.23 - Robuster Twig- und Plugin-Fallback für WBCE 1.6.8 mit Hook-Bridge.
 *
 * 3.1.22 - Einheitlicher Modulname "Captcha & Spam-Schutz".
 *
 * 3.1.21 - Login widget fallback for WBCE 1.7 templates without CAPTCHA output.
 *
 * 3.1.20 - Selected providers are loaded reliably on public login entry points.
 *
 * 3.1.19 - Widget element controls are positioned directly below the preview.
 *
 * 3.1.18 - Settings save asynchronously with busy state, toast and FTAN refresh.
 *
 * 3.1.17 - Robust class-based ALTCHA logo/footer preview toggling.
 *
 * 3.1.16 - Login row stays top-aligned and the redundant dropdown label is removed.
 *
 * 3.1.15 - Central, CSP-safe ALTCHA preview controls including independent logo toggle.
 *
 * 3.1.14 - Login CAPTCHA uses an enable switch with conditional mode selection.
 *
 * 3.1.13 - Preview shows one CAPTCHA field instead of two simultaneous states.
 *
 * 3.1.12 - Original-style ALTCHA preview, independent logo/footer toggles and compact delay input.
 *
 * 3.1.11 - Provider-specific intro, restored preview controls and bottom module note.
 *
 * 3.1.10 - Restored the compact preview width with only 20px additional space.
 *
 * 3.1.9 - Restored the Store-style blue module header without duplicate shell header.
 *
 * 3.1.8 - Provider-owned ALTCHA settings and a wider widget preview column.
 *
 * 3.1.7 - ALTCHA footer and logo visibility use consistent toggle switches.
 *
 * 3.1.6 - Layout aligned with Store and shared Admin-Tools components.
 *
 * 3.1.5 - Fallback when ALTCHA is unavailable and final German module name.
 *
 * 3.1.4 - Provider selection is shown only when multiple providers are installed.
 *
 * 3.1.3 - ALTCHA is supplied exclusively by the separate provider module.
 *
 * 3.1.2 - Provider cards and dynamic provider-specific settings visibility.
 *
 * 3.1.0 - Provider hooks, login/password-reset controls and isolated 1.6.8 compatibility.
 *
 * 3.0.2 - fix math-captcha fallback which used wrong session key
 *         (Christian M. Stefan)
 *
 * 3.0.1 - Implementation of customization means for the ALTCHA-Captcha skin
 *         (Christian M. Stefan)
 *
 * 3.0.0 - Introduction of ALTCHA-Captcha as the only captcha mode available
 *         Intoduction of class Captcha, globally available via the initialize.php
 *         (Christian M. Stefan)
 * 
 * 2.0.6 - set $core var, remove deprecated $module_level var
 * 
 * 2.0.5 - php 8.1 fixes
 * 
 * 2.0.4 - cs fixed files
 *
 * 2.0.3 - Add module_level core status
 *       - Update module_platform 
 * 
 * 2.0.2 - Add Admintool Icon
 *
 * 2.0.1 - Add module_name translation
 *
 **/
