<?php

if (!function_exists('fpc_language')) {
function fpc_language()
{
    static $strings;
    if (is_array($strings)) return $strings;
    $language = defined('LANGUAGE') ? strtoupper((string) LANGUAGE) : 'EN';
    $file = __DIR__ . '/languages/' . preg_replace('/[^A-Z]/', '', $language) . '.php';
    if (!is_file($file)) $file = __DIR__ . '/languages/EN.php';
    $strings = require $file;
    return $strings;
}

}

if (!function_exists('fpc_t')) {
function fpc_t($key)
{
    $strings = fpc_language();
    return isset($strings[$key]) ? $strings[$key] : $key;
}

}
