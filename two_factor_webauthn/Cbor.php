<?php
require_once WB_PATH.'/modules/two_factor/Language.php';

final class WbceWebAuthnCbor
{
    public static function decode($data, &$offset = 0, $depth = 0)
    {
        $data = (string)$data;
        if ((int)$depth > 32) self::invalid();
        if ($offset < 0 || $offset >= strlen($data)) self::invalid();
        $first = ord($data[$offset++]);
        $major = $first >> 5;
        $additional = $first & 31;
        $length = self::readLength($data, $offset, $additional);
        if ($major === 0) return $length;
        if ($major === 1) return -1 - $length;
        if ($major === 2 || $major === 3) {
            self::requireBytes($data, $offset, $length);
            $value = substr($data, $offset, $length);
            $offset += $length;
            return $value;
        }
        if ($major === 4 || $major === 5) {
            if ($length > 1024) self::invalid();
            $value = array();
            for ($i = 0; $i < $length; ++$i) {
                if ($major === 4) $value[] = self::decode($data, $offset, $depth + 1);
                else {
                    $key = self::decode($data, $offset, $depth + 1);
                    if (!is_int($key) && !is_string($key)) self::invalid();
                    $value[$key] = self::decode($data, $offset, $depth + 1);
                }
            }
            return $value;
        }
        if ($major === 7) {
            if ($additional === 20) return false;
            if ($additional === 21) return true;
            if ($additional === 22) return null;
        }
        throw new RuntimeException(wbce_two_factor_t('cbor_type_unsupported', array(), 'two_factor_webauthn'));
    }

    private static function readLength($data, &$offset, $additional)
    {
        if ($additional < 24) return $additional;
        $sizes = array(24 => 1, 25 => 2, 26 => 4);
        if (!isset($sizes[$additional])) throw new RuntimeException(wbce_two_factor_t('cbor_length_unsupported', array(), 'two_factor_webauthn'));
        $size = $sizes[$additional];
        self::requireBytes($data, $offset, $size);
        if ($size === 1) $value = ord($data[$offset]);
        else {
            $result = unpack($size === 2 ? 'nvalue' : 'Nvalue', substr($data, $offset, $size));
            $value = (int)$result['value'];
        }
        $offset += $size;
        return $value;
    }

    private static function requireBytes($data, $offset, $amount)
    {
        if ($amount < 0 || $offset < 0 || $amount > strlen($data) - $offset) self::invalid();
    }

    private static function invalid()
    {
        throw new RuntimeException(wbce_two_factor_t('cbor_invalid', array(), 'two_factor_webauthn'));
    }
}
