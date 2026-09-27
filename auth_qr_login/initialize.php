<?php
if (!defined('WB_PATH') || !interface_exists('WbceAuthenticationProviderInterface') || !class_exists('WbceAuthenticationProviderManager')) return;
require_once __DIR__ . '/Provider.php';
WbceAuthenticationProviderManager::register(new WbceAuthQrLoginProvider());

if (class_exists('WbceAuthenticationRegistry')) WbceAuthenticationRegistry::register('auth_qr_login',['directory'=>'auth_qr_login','name'=>'QR-Code-Anmeldung','description'=>'Anmeldung durch eine bestätigte QR-Code-Sitzung.','icon'=>'fa fa-qrcode']);
