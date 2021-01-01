<?php

namespace OFFLINE\Bootstrapper\October\Downloader;

use OFFLINE\Bootstrapper\October\Util\Composer;
use Symfony\Component\Process\Exception\LogicException;
use Symfony\Component\Process\Exception\RuntimeException;
use Symfony\Component\Process\Process;

class OctoberCms
{
    /**
     * @var Composer
     */
    protected $composer;

    /**
     * Downloads and installs October CMS.
     *
     */
    public function __construct(Composer $composer)
    {
        $this->composer = $composer;
    }

    /**
     * Install latest October CMS.
     *
     * @param bool $force
     *
     * @return $this
     * @throws RuntimeException
     * @throws LogicException
     */
    public function install($force = false)
    {
        if ($this->alreadyInstalled($force)) {
            throw new \LogicException('-> October is already installed. Use --force to reinstall.');
        }

        $this->createProject()
             ->cleanUp();

        return $this;
    }

    /**
     * Create a new October CMS project using composer
     * 
     * @return $this
     */
    protected function createProject()
    {
        $this->composer->createProject(
            'october/october',
            'october-1.1',
            '1.1.*'
        );

        return $this;
    }

    /**
     * Move folder contents one level up, remove temporary folder
     *
     * @return $this
     * @throws RuntimeException
     * @throws LogicException
     */
    protected function cleanUp()
    {
        $directory = getcwd();
        $source    = $directory . DS . 'october-1.1';

        (new Process(sprintf('mv %s %s', $source . '/*', $directory)))->run();
        (new Process(sprintf('rm -rf %s', $source)))->run();

        if (is_dir($source)) {
            echo "<comment>Install directory could not be removed. Delete ${source} manually</comment>";
        }

        return $this;
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
