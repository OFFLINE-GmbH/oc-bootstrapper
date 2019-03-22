<?php

namespace OFFLINE\Bootstrapper\October\Manager;

use OFFLINE\Bootstrapper\October\Util\Artisan;
use OFFLINE\Bootstrapper\October\Util\ManageDirectory;

/**
 * Plugin manager base class
 */
class BaseManager
{
    use ManageDirectory;

    /**
     * @var Artisan
     */
    protected $artisan;

    /**
     * @var string
     */
    protected $php;

    public function __construct()
    {
        $this->artisan = new Artisan();

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