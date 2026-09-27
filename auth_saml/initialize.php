<?php
if (!defined('WB_PATH') || !interface_exists('WbceAuthenticationProviderInterface') || !class_exists('WbceAuthenticationProviderManager')) return;
require_once __DIR__ . '/Provider.php';
WbceAuthenticationProviderManager::register(new WbceAuthSamlProvider());

if (class_exists('WbceAuthenticationRegistry')) WbceAuthenticationRegistry::register('auth_saml',['directory'=>'auth_saml','name'=>'SAML 2.0','description'=>'Anmeldung über einen SAML-2.0-Identitätsanbieter.','icon'=>'fa fa-id-badge']);
