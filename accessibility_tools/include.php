<?php
defined('WB_PATH') or die('No direct access');

// WBCE 1.6.8 lädt registrierte Frontend-Includes zuverlässig, auch wenn die
// frühe Modulinitialisierung in einer Installation nicht nachgerüstet wurde.
// Injector::register() ist idempotent; native Hooks und Bridge bleiben daher
// weiterhin die bevorzugten Wege und erzeugen keine doppelte Ausgabe.
require_once __DIR__.'/src/Injector.php';
WbceAccessibilityToolsInjector::register();
