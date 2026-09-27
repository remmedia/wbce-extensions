<?php
if (!defined('WB_PATH') || !interface_exists('WbceAuthenticationProviderInterface') || !class_exists('WbceAuthenticationProviderManager')) return;
require_once __DIR__ . '/Provider.php';
WbceAuthenticationProviderManager::register(new WbceAuthWebauthnProvider());

if (class_exists('WbceAuthenticationRegistry')) WbceAuthenticationRegistry::register('auth_webauthn',['directory'=>'auth_webauthn','name'=>'Passkey-Anmeldung','description'=>'Passkeys und Sicherheitsschlüssel für die primäre Anmeldung.','icon'=>'fa fa-key']);
