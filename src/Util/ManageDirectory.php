<?php

namespace OFFLINE\Bootstrapper\October\Util;

use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Process\Process;

/**
 * Trait with directory helpers
 */
trait ManageDirectory
{


    /**
     * Delete a file. Fallback to OS native rm command if the file to be deleted is write protected.
     *
     * @param string $file
     */
    public function unlink($file)
    {
        if (is_writable($file)) {
            unlink($file);
        } else {
            // Just to be sure that we don't delete "too much" by accident...
            if (\in_array($file, ['*', '**', '.', '..', '/', '/*'])) {
                return;
            }

            // If there are write-protected files present (primarily on Windows) we can use
            // the force mode of rm to remove it. PHP itself won't delete write-protected files.
            $command = stripos(PHP_OS, 'WIN') === 0 ? 'rm /f' : 'rm -f';
            $file    = escapeshellarg($file);

            (new Process($command . ' ' . $file))->setTimeout(60)->run();
        }
    }

    /**
     * Removes a directory recursively.
     *
     * @param $dir
     *
     * @return mixed
     */
    public function rmdir($dir)
    {
        $entries = array_diff(scandir($dir), ['.', '..']);
        foreach ($entries as $entry) {
            $path = $dir . DS . $entry;
            is_dir($path) ? $this->rmdir($path) : $this->unlink($path);
        }

        return rmdir($dir);
    }

    /**
     * Creates a directory.
     *
     * @param $dir
     *
     * @return mixed
     * @throws \RuntimeException
     */
    public function mkdir($dir)
    {
        if ( ! @mkdir($dir) && ! is_dir($dir)) {
            throw new RuntimeException('Could not create directory: ' . $dir);
        }

        return $dir;
    }

    /**
     * Checks if a directory is empty.
     *
     * @param $themeDir
     *
     * @return bool
     */
    public function isEmpty($themeDir)
    {
        return count(glob($themeDir . '/*')) === 0;
    }

    /**
     * Returns current working directory, mimic of `pwd` console command
     * 
     * @return string current path
     */
    public function pwd()
    {
        return getcwd() . DS;
    }
}
