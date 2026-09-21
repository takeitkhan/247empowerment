<?php
/**
 * AES-256-CBC encryption for stored tokens/secrets, keyed from wp_salt('auth').
 */

if (!defined('ABSPATH')) {
    exit;
}

class MM_Social_Poster_Crypto
{
    const PREFIX = 'enc:';

    private static function key()
    {
        return hash('sha256', wp_salt('auth'), true);
    }

    public static function is_encrypted($value)
    {
        return is_string($value) && strpos($value, self::PREFIX) === 0;
    }

    public static function encrypt($plain)
    {
        if ($plain === '' || $plain === null) {
            return '';
        }
        $iv = random_bytes(16);
        $cipher = openssl_encrypt((string) $plain, 'aes-256-cbc', self::key(), OPENSSL_RAW_DATA, $iv);
        if ($cipher === false) {
            return '';
        }
        return self::PREFIX . base64_encode($iv . $cipher);
    }

    public static function decrypt($encrypted)
    {
        if (empty($encrypted)) {
            return '';
        }
        if (self::is_encrypted($encrypted)) {
            $encrypted = substr($encrypted, strlen(self::PREFIX));
        }
        $raw = base64_decode($encrypted, true);
        if ($raw === false || strlen($raw) < 17) {
            return '';
        }
        $iv = substr($raw, 0, 16);
        $cipher = substr($raw, 16);
        $plain = openssl_decrypt($cipher, 'aes-256-cbc', self::key(), OPENSSL_RAW_DATA, $iv);
        return $plain === false ? '' : $plain;
    }
}
