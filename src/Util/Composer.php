<?php

namespace OFFLINE\Bootstrapper\October\Util;


use Symfony\Component\Process\Process;

/**
 * Class Composer
 * @package OFFLINE\Bootstrapper\October\Util
 */
class Composer
{

    /**
     * @var string
     */
    protected $composer;

    /**
     * Run Composer commands.
     */
    public function __construct()
    {
        $this->composer = $this->findComposer();
    }

    /**
     * Get the composer command for the environment.
     *
     * @return string
     */
    protected function findComposer()
    {
        if (file_exists(getcwd() . DS . 'composer.phar')) {
            return '"' . PHP_BINARY . '" composer.phar';
        }

        return 'composer';
    }

    /**
     * Composer install
     *
     * @return void
     * @throws \Symfony\Component\Process\Exception\InvalidArgumentException
     * @throws \Symfony\Component\Process\Exception\RuntimeException
     * @throws \Symfony\Component\Process\Exception\LogicException
     */
    public function install()
    {
        (new Process($this->composer . ' install --no-scripts --no-interaction --prefer-dist'))
            ->setTimeout(3600)
            ->run();
    }

    /**
     * Composer update --lock
     *
     * @return void
     * @throws \Symfony\Component\Process\Exception\InvalidArgumentException
     * @throws \Symfony\Component\Process\Exception\RuntimeException
     * @throws \Symfony\Component\Process\Exception\LogicException
     */
    public function updateLock()
    {
        (new Process($this->composer . ' update --no-scripts --no-interaction --prefer-dist --lock'))
            ->setTimeout(3600)
            ->run();
    }

    /**
     * Composer require (if not already there)
     *
     * @return void
     * @throws \Symfony\Component\Process\Exception\LogicException
     * @throws \Symfony\Component\Process\Exception\RuntimeException
     * @throws \Symfony\Component\Process\Exception\InvalidArgumentException
     */
    public function addDependency($package)
    {
        // If the package is already installed don't add it again
        $slashed = str_replace('/', '\/', $package);
        if(preg_grep('/' . $slashed . '/', file(getcwd() . DS . 'composer.json'))) {
            return true;
        }

        $package = escapeshellarg($package);

        (new Process($this->composer . ' require ' . $package . ' --no-interaction'))
            ->setTimeout(3600)
            ->run();
    }

    /**
     * Composer require <package> <version>
     *
     * @return void
     * @throws \Symfony\Component\Process\Exception\LogicException
     * @throws \Symfony\Component\Process\Exception\RuntimeException
     * @throws \Symfony\Component\Process\Exception\InvalidArgumentException
     */
    public function requireVersion($package, $version)
    {
        $package = escapeshellarg($package);
        $version = escapeshellarg($version);

        (new Process($this->composer . ' require ' . $package . ' ' . $version . ' --no-interaction'))
            ->setTimeout(3600)
            ->run();
    }
}