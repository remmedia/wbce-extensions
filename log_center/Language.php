<?php
defined('WB_PATH') or die('No direct access');
function log_center_language()
{
    $requested = (string)($_GET['lang'] ?? $_POST['lang'] ?? '');
    $userLanguage = '';
    global $admin, $database;
    $userId = is_object($admin) && method_exists($admin, 'get_user_id') ? (int)$admin->get_user_id() : 0;
    if ($userId > 0 && isset($database) && is_object($database)) {
        try {
            if (method_exists($database, 'fetchValue')) $userLanguage = (string)$database->fetchValue('SELECT `language` FROM `{TP}users` WHERE `user_id` = ?', array($userId));
            elseif (method_exists($database, 'get_one')) $userLanguage = (string)$database->get_one('SELECT `language` FROM `{TP}users` WHERE `user_id` = '.$userId);
        } catch (Throwable $e) { $userLanguage = ''; }
    }
    $session = (string)($_SESSION['LANGUAGE'] ?? $_SESSION['LANG'] ?? '');
    $defined = defined('LANGUAGE') ? (string)LANGUAGE : '';
    foreach (array($requested, $userLanguage, $session, $defined, 'EN') as $language) {
        $code = strtoupper(substr($language, 0, 2));
        if ($code !== '' && is_file(__DIR__.'/languages/'.$code.'.php')) return $code;
    }
    return 'EN';
}
function log_center_text($key)
{
    static $text, $loadedLanguage = '';
    $code = log_center_language();
    if (!is_array($text) || $loadedLanguage !== $code) {
        $en = require __DIR__.'/languages/EN.php';
        $selected = is_file(__DIR__.'/languages/'.$code.'.php') ? require __DIR__.'/languages/'.$code.'.php' : array();
        $text = array_replace($en, is_array($selected) ? $selected : array());
        $loadedLanguage = $code;
    }
    return isset($text[$key]) ? (string)$text[$key] : (string)$key;
}
