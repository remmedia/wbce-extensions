<?php

final class WbceTotp
{
    public static function generateSecret($bytes = 20)
    {
        return self::base32Encode(random_bytes($bytes));
    }

    public static function verify($secret, $code, $time = null, $window = 1, &$matchedCounter = null)
    {
        $code = preg_replace('/\D/', '', (string)$code);
        if (strlen($code) !== 6) {
            return false;
        }
        $counter = intdiv($time === null ? time() : (int)$time, 30);
        for ($offset = -$window; $offset <= $window; $offset++) {
            $candidateCounter = $counter + $offset;
            if (hash_equals(self::at($secret, $candidateCounter), $code)) {
                $matchedCounter = $candidateCounter;
                return true;
            }
        }
        return false;
    }

    public static function at($secret, $counter)
    {
        $binary = self::base32Decode($secret);
        $high = intdiv((int)$counter, 4294967296);
        $low = (int)$counter % 4294967296;
        $hash = hash_hmac('sha1', pack('N2', $high, $low), $binary, true);
        $offset = ord($hash[19]) & 0x0f;
        $value = unpack('N', substr($hash, $offset, 4))[1] & 0x7fffffff;
        return str_pad((string)($value % 1000000), 6, '0', STR_PAD_LEFT);
    }

    private static function base32Encode($data)
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $bits = '';
        foreach (str_split($data) as $char) {
            $bits .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
        }
        $result = '';
        foreach (str_split($bits, 5) as $chunk) {
            $result .= $alphabet[bindec(str_pad($chunk, 5, '0'))];
        }
        return $result;
    }

    private static function base32Decode($data)
    {
        $alphabet = array_flip(str_split('ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'));
        $bits = '';
        foreach (str_split(strtoupper(preg_replace('/[^A-Z2-7]/i', '', $data))) as $char) {
            $bits .= str_pad(decbin($alphabet[$char]), 5, '0', STR_PAD_LEFT);
        }
        $result = '';
        foreach (str_split($bits, 8) as $chunk) {
            if (strlen($chunk) === 8) {
                $result .= chr(bindec($chunk));
            }
        }
        return $result;
    }
}

