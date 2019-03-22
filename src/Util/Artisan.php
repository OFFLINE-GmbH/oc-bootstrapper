<?php

namespace OFFLINE\Bootstrapper\October\Util;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\RuntimeException;

/**
 * Class Artisan
 * @package OFFLINE\Bootstrapper\October\Util
 */
class Artisan
{
    /**
     * @var string
     */
    protected $php;

    public function __construct(string $php = 'php')
    {
        $this->setPhp($php);
    }

    /**
     * Set PHP version to be used in console commands
     */
    public function setPhp(string $php = 'php')
    {
        $this->php = $php;
    }

    public function call(string $command)
    {
        $exitCode = (new Process($this->php . " artisan plugin:install {$vendor}.{$plugin}"))->run();

        if ($exitCode !== $this::EXIT_CODE_OK) {
            throw new RuntimeException(
                sprintf('Error while installing plugin %s via artisan. Is your database set up correctly?',
                    $vendor . '.' . $plugin
                )
            );
        }
    }
}