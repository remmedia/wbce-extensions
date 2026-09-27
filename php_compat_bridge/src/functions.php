<?php
/** Removable PHP 8.2/8.3 compatibility functions introduced with PHP 8.4. */
if (!function_exists('array_find')) {
    function array_find(array $array, callable $callback)
    {
        foreach ($array as $key => $value) if ($callback($value, $key)) return $value;
        return null;
    }
}
if (!function_exists('array_find_key')) {
    function array_find_key(array $array, callable $callback)
    {
        foreach ($array as $key => $value) if ($callback($value, $key)) return $key;
        return null;
    }
}
if (!function_exists('array_any')) {
    function array_any(array $array, callable $callback): bool
    {
        foreach ($array as $key => $value) if ($callback($value, $key)) return true;
        return false;
    }
}
if (!function_exists('array_all')) {
    function array_all(array $array, callable $callback): bool
    {
        foreach ($array as $key => $value) if (!$callback($value, $key)) return false;
        return true;
    }
}
if (!function_exists('mb_ucfirst') && function_exists('mb_substr')) {
    function mb_ucfirst(string $string, ?string $encoding = null): string
    {
        $encoding = $encoding ?: mb_internal_encoding();
        return mb_strtoupper(mb_substr($string, 0, 1, $encoding), $encoding).mb_substr($string, 1, null, $encoding);
    }
}
if (!function_exists('mb_lcfirst') && function_exists('mb_substr')) {
    function mb_lcfirst(string $string, ?string $encoding = null): string
    {
        $encoding = $encoding ?: mb_internal_encoding();
        return mb_strtolower(mb_substr($string, 0, 1, $encoding), $encoding).mb_substr($string, 1, null, $encoding);
    }
}
if (!function_exists('json_validate')) {
    function json_validate(string $json, int $depth = 512, int $flags = 0): bool
    {
        if ($depth <= 0) throw new ValueError('json_validate(): Argument #2 ($depth) must be greater than 0');
        json_decode($json, null, $depth, $flags);
        return json_last_error() === JSON_ERROR_NONE;
    }
}
if (!function_exists('mb_str_pad') && function_exists('mb_strlen')) {
    function mb_str_pad(string $string, int $length, string $pad_string = ' ', int $pad_type = STR_PAD_RIGHT, ?string $encoding = null): string
    {
        if ($pad_string === '') throw new ValueError('mb_str_pad(): Argument #3 ($pad_string) must be a non-empty string');
        if (!in_array($pad_type, array(STR_PAD_LEFT, STR_PAD_RIGHT, STR_PAD_BOTH), true)) throw new ValueError('mb_str_pad(): Argument #4 ($pad_type) must be STR_PAD_LEFT, STR_PAD_RIGHT, or STR_PAD_BOTH');
        $encoding = $encoding ?: mb_internal_encoding();
        $padding = $length - mb_strlen($string, $encoding);
        if ($padding <= 0) return $string;
        $left = $pad_type === STR_PAD_LEFT ? $padding : ($pad_type === STR_PAD_BOTH ? intdiv($padding, 2) : 0);
        $right = $padding - $left;
        $makePad = static function (int $size) use ($pad_string, $encoding): string {
            if ($size < 1) return '';
            $repeat = (int)ceil($size / mb_strlen($pad_string, $encoding));
            return mb_substr(str_repeat($pad_string, $repeat), 0, $size, $encoding);
        };
        return $makePad($left).$string.$makePad($right);
    }
}
if (!function_exists('grapheme_str_split')) {
    function grapheme_str_split(string $string, int $length = 1): array|false
    {
        if ($length < 1) throw new ValueError('grapheme_str_split(): Argument #2 ($length) must be greater than 0');
        if ($string === '') return array();
        if (preg_match_all('/\X/u', $string, $matches) === false) return false;
        $graphemes = $matches[0];
        return $length === 1 ? $graphemes : array_map(static fn(array $part): string => implode('', $part), array_chunk($graphemes, $length));
    }
}
