<?php

namespace OFFLINE\Bootstrapper\October\Manager;

use OFFLINE\Bootstrapper\October\Util\Artisan;
use OFFLINE\Bootstrapper\October\Util\Composer;
use OFFLINE\Bootstrapper\October\Util\CliIO;
use OFFLINE\Bootstrapper\October\Util\ManageDirectory;

/**
 * Plugin manager base class
 */
class BaseManager
{
    use CliIO, ManageDirectory;

    /**
     * @var Artisan
     */
    protected $artisan;

    /**
     * @var Composer
     */
    protected $composer;

    /**
     * @var string
     */
    protected $php;

    public function __construct()
    {
        $this->artisan = new Artisan();

        $this->composer = new Composer();

        $this->setPhp();
    }

    /**
     * Set PHP version to be used in console commands
     */
    public function setPhp(string $php = 'php')
    {
        $this->php = $php;
        $this->artisan->setPhp($php);
    }
}
