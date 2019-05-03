<?php

namespace OFFLINE\Bootstrapper\October\Util;

use RuntimeException;
use Symfony\Component\Process\Process;

/**
 * Class Artisan
 * @package OFFLINE\Bootstrapper\October\Util
 */
class Artisan
{
    use CliIO;

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
        $proc = new Process($this->php . " artisan " . $command);
        $proc->enableOutput();
        $exitCode = $proc->run();

        if ($exitCode !== $this->exitCodeOk) {
            throw new RuntimeException(
                sprintf("Error running \"{$this->php} artisan {$command}\" command: %s", $proc->getOutput())
            );
        }
    }
}
