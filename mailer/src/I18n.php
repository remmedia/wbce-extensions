<?php
final class WbceMailerI18n
{
    public static function t(string $key, string $fallback = ''): string
    {
        static $messages = null;
        if ($messages === null) {
            $language = strtoupper((string)(defined('LANGUAGE') ? LANGUAGE : 'EN'));
            $file = WB_PATH.'/modules/mailer/languages/'.$language.'.php';
            if (!is_file($file)) $file = WB_PATH.'/modules/mailer/languages/EN.php';
            $messages = is_file($file) ? (array)require $file : [];
        }
        return (string)($messages[$key] ?? $fallback ?: $key);
    }
    public static function label(string $label): string { return self::t('LABEL_'.$label, $label); }
}
