<?php
if (!function_exists('wbce_store_text')) {
    function wbce_store_text($key, array $replace = array())
    {
        static $messages = null;
        if ($messages === null) {
            $language = defined('LANGUAGE') ? strtoupper(substr((string) LANGUAGE, 0, 2)) : 'EN';
            $fallback = require __DIR__.'/languages/EN.php';
            $file = __DIR__.'/languages/'.preg_replace('/[^A-Z]/', '', $language).'.php';
            $messages = $file !== __DIR__.'/languages/EN.php' && is_file($file)
                ? array_replace($fallback, (array) require $file)
                : $fallback;
        }
        $text = isset($messages[$key]) ? (string) $messages[$key] : (string) $key;
        foreach ($replace as $name => $value) $text = str_replace('{'.$name.'}', (string) $value, $text);
        return $text;
    }
}
