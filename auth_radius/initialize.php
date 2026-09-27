<?php
if (!defined('WB_PATH') || !interface_exists('WbceAuthenticationProviderInterface') || !class_exists('WbceAuthenticationProviderManager')) return;
require_once __DIR__ . '/Provider.php';
WbceAuthenticationProviderManager::register(new WbceAuthRadiusProvider());

if (class_exists('WbceAuthenticationRegistry')) WbceAuthenticationRegistry::register('auth_radius',['directory'=>'auth_radius','name'=>'RADIUS','description'=>'Anmeldung gegen einen RADIUS-Server.','icon'=>'fa fa-server']);
