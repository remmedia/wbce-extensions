<?php

/** Reads literal metadata assignments without executing package PHP code. */
final class WbceRepositoryInfoParser
{
    public static function parse($source)
    {
        $allowed = array(
            'module_directory', 'module_name', 'module_version', 'module_description', 'module_platform', 'module_function', 'module_dependencies', 'module_requires_any',
            'template_directory', 'template_name', 'template_version', 'template_description', 'template_platform', 'template_function',
            'language_code', 'language_name', 'language_version', 'language_description', 'language_platform', 'language_author',
        );
        $result = array();
        $tokens = token_get_all((string)$source);
        $count = count($tokens);
        for ($i = 0; $i < $count - 2; $i++) {
            if (!is_array($tokens[$i]) || $tokens[$i][0] !== T_VARIABLE) {
                continue;
            }
            $name = substr($tokens[$i][1], 1);
            if (!in_array($name, $allowed, true)) {
                continue;
            }
            $j = $i + 1;
            while ($j < $count && is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE) $j++;
            if ($j >= $count || $tokens[$j] !== '=') continue;
            $j++;
            while ($j < $count && is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE) $j++;
            if ($j < $count && is_array($tokens[$j]) && $tokens[$j][0] === T_CONSTANT_ENCAPSED_STRING) {
                $result[$name] = self::literal($tokens[$j][1]);
            }
        }
        return $result;
    }

    private static function literal($value)
    {
        $quote = substr($value, 0, 1);
        $body = substr($value, 1, -1);
        return $quote === "'"
            ? str_replace(array("\\'", "\\\\"), array("'", "\\"), $body)
            : stripcslashes($body);
    }
}
