<?php

namespace OFFLINE\Bootstrapper\October\Util;


/**
 * Class KeyGenerator
 * @package OFFLINE\Bootstrapper\October\Util
 */
class KeyGenerator
{
    /**
     * @param int $length
     *
     * @return string
     */
    public function generate($length = 32)
    {
        $string = '';
        while (($len = strlen($string)) < $length) {
            $size  = $length - $len;
            $bytes = $this->randomBytes($size);
            $string .= substr(str_replace(['/', '+', '='], '', base64_encode($bytes)), 0, $size);
        }

        return $string;
    }

    /**
     * @param int $length
     *
     * @return string
     */
    private function randomBytes($length = 32)
    {
        $bytes = 'CHANGE_ME!!!!!!!';

        if (PHP_MAJOR_VERSION >= 7 || defined('RANDOM_COMPAT_READ_BUFFER')) {
            $bytes = random_bytes($length);
        } elseif (function_exists('openssl_random_pseudo_bytes')) {
            $bytes = openssl_random_pseudo_bytes($length, $strong);
        }

        return $bytes;
    }

}