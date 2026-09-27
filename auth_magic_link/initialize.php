<?php
if (!defined('WB_PATH') || !interface_exists('WbceAuthenticationProviderInterface') || !class_exists('WbceAuthenticationProviderManager')) return;
require_once __DIR__ . '/Provider.php';
WbceAuthenticationProviderManager::register(new WbceAuthMagicLinkProvider());

if (class_exists('WbceAuthenticationRegistry')) WbceAuthenticationRegistry::register('auth_magic_link',['directory'=>'auth_magic_link','name'=>'Magic Link','description'=>'Passwortlose Anmeldung über zeitlich begrenzte E-Mail-Links.','icon'=>'fa fa-link']);
