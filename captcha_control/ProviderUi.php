<?php

/** Shared, theme-neutral UI helpers for separately installable CAPTCHA providers. */
final class WbceCaptchaProviderUi
{
    public static function language($providerDirectory, array $fallback)
    {
        $language = defined('LANGUAGE') ? strtoupper((string) LANGUAGE) : 'EN';
        $file = rtrim((string) $providerDirectory, '/\\') . '/languages/' . $language . '.php';
        if (!is_file($file)) $file = rtrim((string) $providerDirectory, '/\\') . '/languages/EN.php';
        $CAPTCHA_PROVIDER = array();
        if (is_file($file)) include $file;
        return array_merge($fallback, is_array($CAPTCHA_PROVIDER) ? $CAPTCHA_PROVIDER : array());
    }

    public static function escape($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

    public static function fieldset($legend, $content)
    {
        return '<fieldset class="captcha-provider-config"><legend>' . self::escape($legend) . '</legend>' . $content . '</fieldset>';
    }

    public static function row($label, $control, $hint = '')
    {
        return '<div class="cp-setting-row"><div class="cp-setting-name">' . self::escape($label) . '</div><div class="cp-setting-value">' . $control
            . ($hint !== '' ? '<div class="cp-setting-hint">' . self::escape($hint) . '</div>' : '') . '</div></div>';
    }

    public static function input($type, $name, $value = '', array $attributes = array())
    {
        $html = '<input type="' . self::escape($type) . '" name="' . self::escape($name) . '" value="' . self::escape($value) . '"';
        foreach ($attributes as $key => $attributeValue) {
            if ($attributeValue === null || $attributeValue === false) continue;
            $html .= ' ' . self::escape($key);
            if ($attributeValue !== true) $html .= '="' . self::escape($attributeValue) . '"';
        }
        return $html . '>';
    }

    public static function select($name, $selected, array $options)
    {
        $html = '<select name="' . self::escape($name) . '">';
        foreach ($options as $value => $label) {
            $html .= '<option value="' . self::escape($value) . '"' . ((string) $value === (string) $selected ? ' selected' : '') . '>' . self::escape($label) . '</option>';
        }
        return $html . '</select>';
    }

    public static function storedSecret($isStored, $storedText)
    {
        return $isStored ? '<span class="captcha-provider-stored"><i class="fa fa-check" aria-hidden="true"></i> ' . self::escape($storedText) . '</span>' : '';
    }
}
