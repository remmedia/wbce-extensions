<?php
if (!defined('WB_PATH') || !interface_exists('WbceAuthenticationProviderInterface') || !class_exists('WbceAuthenticationProviderManager')) return;
require_once __DIR__ . '/Provider.php';
WbceAuthenticationProviderManager::register(new WbceAuthOidcProvider());

if (class_exists('WbceAuthenticationRegistry')) WbceAuthenticationRegistry::register('auth_oidc',['directory'=>'auth_oidc','name'=>'OpenID Connect','description'=>'Anmeldung über einen OpenID-Connect-Identitätsanbieter.','icon'=>'fa fa-openid']);
