<?php

namespace OFFLINE\Bootstrapper\October\Util;


use Symfony\Component\Process\Process;

class Composer
{

    protected $composer;

    /**
     * Run Composer commands.
     */
    public function __construct()
    {
        $this->composer = $this->findComposer();
    }

    /**
     * Composer install
     *
     * @return void
     */
    public function install()
    {
        (new Process($this->composer . ' install --no-scripts'))->run();
    }

    /**
     * Get the composer command for the environment.
     *
     * @return string
     */
    protected function findComposer()
    {
        if (file_exists(getcwd() . '/composer.phar')) {
            return '"' . PHP_BINARY . '" composer.phar';
        }

        return 'composer';
    }
}