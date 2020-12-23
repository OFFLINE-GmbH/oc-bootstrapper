<?php

namespace OFFLINE\Bootstrapper\October\Downloader;


use GuzzleHttp\Client;
use Symfony\Component\Process\Exception\LogicException;
use Symfony\Component\Process\Exception\RuntimeException;
use Symfony\Component\Process\Process;
use ZipArchive;

class OctoberCms
{
    protected $zipFile;

    /**
     * Downloads and extracts October CMS.
     *
     */
    public function __construct()
    {
        $this->zipFile = $this->makeFilename();
    }

    /**
     * Download latest October CMS.
     *
     * @param bool $force
     *
     * @return $this
     * @throws RuntimeException
     * @throws LogicException
     */
    public function download($force = false)
    {
        if ($this->alreadyInstalled($force)) {
            throw new \LogicException('-> October is already installed. Use --force to reinstall.');
        }

        $this->fetchZip()
             ->extract()
             ->fetchHtaccess()
             ->cleanUp()
             ->setMaster();

        return $this;
    }

    /**
     * Download the temporary Zip to the given file.
     *
     * @return $this
     * @throws RuntimeException
     * @throws LogicException
     */
    protected function fetchZip()
    {
        $response = (new Client)->get('https://github.com/octobercms/october/archive/1.0.zip');
        file_put_contents($this->zipFile, $response->getBody());

        return $this;
    }

    /**
     * Extract the zip file into the given directory.
     *
     * @return $this
     */
    protected function extract()
    {
        $archive = new ZipArchive;
        $archive->open($this->zipFile);
        $archive->extractTo(getcwd());
        $archive->close();

        return $this;
    }

    /**
     * Download the latest .htaccess file from GitHub separately
     * since ZipArchive does not support extracting hidden files.
     *
     * @return $this
     */
    protected function fetchHtaccess()
    {
        $contents = file_get_contents('https://raw.githubusercontent.com/octobercms/october/1.0/.htaccess');
        file_put_contents(getcwd() . DS . '.htaccess', $contents);

        return $this;
    }


    /**
     * Since we don't want any unstable updates we fix
     * the libraries to the master branch.
     *
     * @return $this
     */
    protected function setMaster()
    {
        $json = getcwd() . DS . 'composer.json';

        $contents = file_get_contents($json);

        $contents = preg_replace_callback(
            '/october\/(?:rain|system|backend|cms)":\s"([^"]+)"/m',
            function ($treffer) {
                return str_replace($treffer[1], '~1.0', $treffer[0]);
            },
            $contents
        );

        file_put_contents($json, $contents);

        return $this;
    }

    /**
     * Remove the Zip file, move folder contents one level up.
     *
     * @return $this
     * @throws RuntimeException
     * @throws LogicException
     */
    protected function cleanUp()
    {
        @chmod($this->zipFile, 0777);
        @unlink($this->zipFile);

        $directory = getcwd();
        $source    = $directory . DS . 'october-1.0';

        (new Process(sprintf('mv %s %s', $source . '/*', $directory)))->run();
        (new Process(sprintf('rm -rf %s', $source)))->run();

        if (is_dir($source)) {
            echo "<comment>Install directory could not be removed. Delete ${source} manually</comment>";
        }

        return $this;
    }

    /**
     * Generate a random temporary filename.
     *
     * @return string
     */
    protected function makeFilename()
    {
        return getcwd() . DS . 'october_' . md5(time() . uniqid('oc-', true)) . '.zip';
    }

    /**
     * @param $force
     *
     * @return bool
     */
    protected function alreadyInstalled($force)
    {
        return ! $force && is_dir(getcwd() . DS . 'bootstrap') && is_dir(getcwd() . DS . 'modules');
    }

}
