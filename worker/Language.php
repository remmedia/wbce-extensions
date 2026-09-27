<?php
require_once __DIR__.'/DatabaseCompatibility.php';
if (!function_exists('worker_language_code')) {
    function worker_language_code()
    {
        static $resolvedLanguage = null;
        if (is_string($resolvedLanguage)) { return $resolvedLanguage; }
        global $admin, $database;
        $language = '';
        $userId = 0;
        if (isset($admin) && is_object($admin) && method_exists($admin, 'get_user_id')) {
            $userId = (int)$admin->get_user_id();
        } elseif (isset($_SESSION['USER_ID'])) {
            $userId = (int)$_SESSION['USER_ID'];
        }
        if ($userId > 0 && isset($database) && is_object($database)) {
            $sql = 'SELECT `language` FROM `{TP}users` WHERE `user_id`=' . $userId;
            // The compatibility helper uses fetchValue() or a normal result
            // row before its isolated WBCE 1.6.8 get_one() fallback.
            $selected = worker_db_value($database, $sql);
            if (is_string($selected)) { $language = $selected; }
        }
        if ($language === '' && isset($_SESSION['LANGUAGE'])) { $language = (string)$_SESSION['LANGUAGE']; }
        if ($language === '' && defined('LANGUAGE')) { $language = (string)LANGUAGE; }
        $language = strtoupper(substr(trim($language), 0, 2));
        $resolvedLanguage = preg_match('/^[A-Z]{2}$/', $language) ? $language : 'EN';
        return $resolvedLanguage;
    }
}
if (!function_exists('worker_t')) {
    function worker_t($key, array $replace = array())
    {
        static $languageSets = array();
        $language = worker_language_code();
        if (!isset($languageSets[$language])) {
            $workerLanguageTexts = array();
            require __DIR__.'/languages/EN.php';
            $texts = $workerLanguageTexts;
            if ($language !== 'EN' && preg_match('/^[A-Z]{2}$/', $language) && is_file(__DIR__.'/languages/'.$language.'.php')) {
                $workerLanguageTexts = array();
                require __DIR__.'/languages/'.$language.'.php';
                $texts = array_merge($texts, $workerLanguageTexts);
            }
            $languageSets[$language] = $texts;
        }
        $texts = $languageSets[$language];
        $text = isset($texts[$key]) ? $texts[$key] : $key;
        return $replace ? strtr($text, $replace) : $text;
    }
}
