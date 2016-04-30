<?php

namespace OFFLINE\Bootstrapper\October\Installer;


use GitElephant\Repository;
use OFFLINE\Bootstrapper\October\Config\Config;
use Symfony\Component\Process\Process;

/**
 * Class ThemeInstaller
 * @package OFFLINE\Bootstrapper\October\Installer
 */
class ThemeInstaller extends Installer
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * ThemeInstaller constructor.
     *
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     *
     * @throws \Symfony\Component\Process\Exception\LogicException
     * @throws \Symfony\Component\Process\Exception\RuntimeException
     * @throws \Symfony\Component\Process\Exception\InvalidArgumentException
     */
    public function install()
    {

        list($theme, $remote) = $this->parse($this->config->theme);
        if ($remote === false) {
            (new Process("php artisan plugin:install {$theme}"))->run();
            return;
        }

        $themeDir = getcwd() . DS . implode(DS, ['themes', $theme]);
        $this->mkdir($themeDir);

        $repo = Repository::open($themeDir);
        try {
            $repo->cloneFrom($remote, $themeDir);
        } catch (\RuntimeException $e) {
            throw new \RuntimeException('Error while cloning theme repo: ' . $e->getMessage());
        }

        $this->cleanup($themeDir);
    }

    /**
     * @param $theme
     *
     * @return mixed
     */
    protected function parse($theme)
    {
        // Vendor.Plugin (Remote)
        preg_match("/([^ ]+)(?: ?\(([^\)]+))?/", $theme, $matches);

        array_shift($matches);

        if (count($matches) < 2) {
            $matches[1] = false;
        }

        return $matches;
    }
}