<?php

namespace OFFLINE\Bootstrapper\October\Util;


use RuntimeException;

/**
 * Class KeyGenerator
 * @package OFFLINE\Bootstrapper\October\Util
 */
class KeyGenerator
{
    /**
     * Generate the application's key.
     *
     * @param int $length
     *
     * @return string
     * @throws \Exception
     */
    public function generate($length = 32)
    {
        $string = '';
        while (($len = strlen($string)) < $length) {
            $size   = $length - $len;
            $bytes  = $this->randomBytes($size);
            $string .= substr(str_replace(['/', '+', '='], '', base64_encode($bytes)), 0, $size);
        }

        return $string;
    }

    /**
     * Generate a random string.
     *
     * @param int $length
     *
     * @return string
     * @throws \Exception
     */
    private function randomBytes($length = 32)
    {
        $bytes = 'CHANGE_ME!!!!!!!';

        if (PHP_MAJOR_VERSION >= 7 || defined('RANDOM_COMPAT_READ_BUFFER')) {
            $bytes = random_bytes($length);
        } elseif (function_exists('openssl_random_pseudo_bytes')) {
            $bytes = openssl_random_pseudo_bytes($length, $strong);
        }

        if ($bytes === false) {
            throw new RuntimeException('Failed to generate random bytes.');
        }

        return $bytes;
    }

}