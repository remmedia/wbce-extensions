<?php
defined('WB_PATH') or die('Access denied');

function wbce_api_t($key, array $replace = array())
{
    static $strings;
    if (!is_array($strings)) {
        $language = defined('LANGUAGE') ? strtoupper(preg_replace('/[^A-Z]/', '', (string) LANGUAGE)) : 'EN';
        $strings = require __DIR__ . '/languages/EN.php';
        $file = __DIR__ . '/languages/' . $language . '.php';
        if ($language !== 'EN' && is_file($file)) $strings = array_merge($strings, require $file);
    }
    $text = isset($strings[$key]) ? (string)$strings[$key] : (string)$key;
    foreach ($replace as $name => $value) $text = str_replace('{'.$name.'}', (string)$value, $text);
    return $text;
}
