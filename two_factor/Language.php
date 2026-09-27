<?php
defined('WB_PATH') or die('No direct access');

if (!class_exists('WbceTwoFactorLanguage', false)) {
final class WbceTwoFactorLanguage
{
    private static $catalogues = array();

    public static function load($moduleDirectory = 'two_factor')
    {
        $base = WB_PATH.'/modules/'.$moduleDirectory.'/languages/';
        $language = defined('LANGUAGE') ? strtoupper((string) LANGUAGE) : (defined('DEFAULT_LANGUAGE') ? strtoupper((string) DEFAULT_LANGUAGE) : 'EN');
        $cacheKey=$moduleDirectory.':'.$language;
        if (isset(self::$catalogues[$cacheKey])) return self::$catalogues[$cacheKey];
        $catalogue = is_file($base.'EN.php') ? (array) require $base.'EN.php' : array();
        if ($language !== 'EN' && preg_match('/^[A-Z]{2}$/', $language) && is_file($base.$language.'.php')) {
            $catalogue = array_replace($catalogue, (array) require $base.$language.'.php');
        }
        return self::$catalogues[$cacheKey] = $catalogue;
    }

    public static function get($key, array $replace = array(), $moduleDirectory = 'two_factor')
    {
        $catalogue = self::load($moduleDirectory);
        $text = isset($catalogue[$key]) ? (string) $catalogue[$key] : (string) $key;
        foreach ($replace as $name => $value) $text = str_replace('{'.$name.'}', (string) $value, $text);
        return $text;
    }
}
}

if (!function_exists('wbce_two_factor_t')) {
    function wbce_two_factor_t($key, array $replace = array(), $moduleDirectory = 'two_factor')
    {
        return WbceTwoFactorLanguage::get($key, $replace, $moduleDirectory);
    }
}
