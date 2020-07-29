<?php

namespace OFFLINE\Bootstrapper\October\Util;

use GitElephant\Repository;

/**
 * This is a simple wrapper class around GitElephant to provide
 * the correct Binary if we run on Windows.
 */
class Git
{
    public static function repo($path)
    {
        return Repository::open($path, self::getBinary());
    }

    public static function clone(string $url, string $to)
    {
        Repository::createFromRemote($url, $to, self::getBinary());
    }

    protected static function getBinary()
    {
        $binary = null;
        if (stripos(PHP_OS, 'WIN') === 0) {
            // Use the default `git` command under Windows. This requires the git binary
            // to be added to the PATH environment variable.
            $binary = 'git';
        }

        return $binary;
    }
}