<?php
if (!class_exists('WbceSecurityCenterLanguage', false)) {
final class WbceSecurityCenterLanguage
{
    private static $translations;

    public static function code()
    {
        $code=defined('LANGUAGE')?(string)LANGUAGE:'';
        if($code==='')$code=substr((string)($_SERVER['HTTP_ACCEPT_LANGUAGE']??'en'),0,2);
        $code=strtoupper(substr(preg_replace('/[^a-z]/i','',$code),0,2));
        return $code!==''?$code:'EN';
    }
    public static function blockPage(array $settings)
    {
        $code=self::code();$fallback='EN';$translations=(array)($settings['block_page_translations']??array());
        $file=__DIR__.'/languages/'.$code.'.php';if(!is_file($file))$file=__DIR__.'/languages/'.$fallback.'.php';
        $defaults=is_file($file)?(array)require $file:array();
        $custom=(array)($translations[$code]??$translations[$fallback]??array());
        return array_merge($defaults,$custom);
    }

    public static function all()
    {
        if (is_array(self::$translations)) {
            return self::$translations;
        }
        $fallback = __DIR__.'/languages/EN.php';
        $selected = __DIR__.'/languages/'.self::code().'.php';
        $base = is_file($fallback) ? (array) require $fallback : array();
        $local = is_file($selected) ? (array) require $selected : array();
        self::$translations = array_replace_recursive($base, $local);
        // Markup translations are directional. An empty German table must replace
        // the English German-to-English table instead of inheriting it.
        if (array_key_exists('markup', $local) && is_array($local['markup'])) {
            self::$translations['markup'] = $local['markup'];
        }
        return self::$translations;
    }

    public static function text($key, $fallback = '', array $replace = array())
    {
        $value = self::all();
        foreach (explode('.', (string) $key) as $part) {
            if (!is_array($value) || !array_key_exists($part, $value)) {
                $value = $fallback !== '' ? $fallback : $key;
                break;
            }
            $value = $value[$part];
        }
        if (!is_scalar($value)) {
            $value = $fallback !== '' ? $fallback : $key;
        }
        return strtr((string) $value, $replace);
    }

    public static function translateMarkup($markup)
    {
        $translations = self::all();
        $replacements = isset($translations['markup']) && is_array($translations['markup'])
            ? $translations['markup']
            : array();
        return $replacements ? strtr((string) $markup, $replacements) : (string) $markup;
    }
}
}
