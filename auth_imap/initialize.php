<?php
if (!defined('WB_PATH') || !interface_exists('WbceAuthenticationProviderInterface') || !class_exists('WbceAuthenticationProviderManager')) return;
require_once __DIR__ . '/Provider.php';
WbceAuthenticationProviderManager::register(new WbceAuthImapProvider());

if (class_exists('WbceAuthenticationRegistry')) WbceAuthenticationRegistry::register('auth_imap',['directory'=>'auth_imap','name'=>'IMAP-Anmeldung','description'=>'Anmeldung gegen einen IMAP-Mailserver.','icon'=>'fa fa-envelope']);
