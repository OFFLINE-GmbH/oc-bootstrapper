<?php

namespace OFFLINE\Bootstrapper\October\Util;

use OFFLINE\Bootstrapper\October\Config\Yaml;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Process\Process;

/**
 * Trait with directory helpers
 */
trait ManageDirectory
{
    /**
     * Copy file from sourceFile to targetFile
     *
     * @param string $sourceFile
     * @param string $targetFile
     */
    public function copy($sourceFile, $targetFile)
    {
        copy($sourceFile, $targetFile);

        if ( ! $this->fileExists($targetFile)) {
            throw new RuntimeException('File ' . $targetFile . ' could not be created');
        }

        return true;
    }

    /**
     * Replaces all placeholder Variables in a file.
     *
     * @param $file
     */
    public function replaceVars($file, array $config)
    {
        $contents = preg_replace_callback('/\%([0-9a-z.]+)\%/', function ($matches) use ($config) {
            $parseHostname = false;
            // app.hostname is a special config entry that returns only the host part of the app.url.
            if ($matches[1] === 'app.hostname') {
                $matches[1] = 'app.url';
                $parseHostname = true;
            }
            $parts = explode('.', $matches[1]);
            $value = $config;
            foreach ($parts as $part) {
                $value = $value[$part] ?? '';
            }
            if (is_array($value)) {
                throw new \LogicException(sprintf('Placeholder "%s" does not select string value', $matches[1]));
            }
            if ($parseHostname) {
                $value = parse_url($value, PHP_URL_HOST);
            }
            return $value;
        }, file_get_contents($file));

        file_put_contents($file, $contents);
    }

    /**
     * Touch file
     *
     * @param string $file relative or absolute path of the file to create
     */
    public function touch($file)
    {
        return touch(realpath($file));
    }

    /**
     * Check if file exists
     *
     * @param string $file relative or absolute path of the file to check existence
     *
     * @return bool
     */
    public function fileExists($file)
    {
        return file_exists(realpath($file));
    }

    /**
     * Get the absolute path of the file.
     *
     * @param string $file relative or absolute path of the file
     *
     * @return string path of the file
     */
    public function path($file)
    {
        return realpath($file) ?: $file;
    }

    /**
     * Check if directory exists
     *
     * @param string $dir relative or absolute path of the directory to check existence
     *
     * @return bool
     */
    public function dirExists($dir)
    {
        return is_dir(realpath($dir));
    }

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
            $file = escapeshellarg($file);

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
        if ( ! $this->dirExists($dir)) {
            return true;
        }

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
     * @param $dir
     *
     * @return bool
     */
    public function isEmpty($dir)
    {
        return count(glob($dir . '/*')) === 0;
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

    /**
     * Removes .git Directories.
     *
     * @param $path
     */
    public function removeGitRepo($path)
    {
        $this->rmdir($path . DS . '.git');
    }
}
