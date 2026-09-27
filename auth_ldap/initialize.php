<?php
if (!defined('WB_PATH') || !interface_exists('WbceAuthenticationProviderInterface') || !class_exists('WbceAuthenticationProviderManager')) return;
require_once __DIR__ . '/Provider.php';
WbceAuthenticationProviderManager::register(new WbceAuthLdapProvider());

if (class_exists('WbceAuthenticationRegistry')) WbceAuthenticationRegistry::register('auth_ldap',['directory'=>'auth_ldap','name'=>'LDAP / Active Directory','description'=>'Anmeldung gegen LDAP oder Microsoft Active Directory.','icon'=>'fa fa-sitemap']);
