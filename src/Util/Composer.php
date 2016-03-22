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
     * @throws \Symfony\Component\Process\Exception\InvalidArgumentException
     * @throws \Symfony\Component\Process\Exception\RuntimeException
     * @throws \Symfony\Component\Process\Exception\LogicException
     */
    public function install()
    {
        (new Process($this->composer . ' install --no-scripts'))
            ->setTimeout(3600)
            ->run();
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