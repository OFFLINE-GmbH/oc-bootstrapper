<?php

namespace OFFLINE\Bootstrapper\October\Installer;


use GitElephant\Repository;
use OFFLINE\Bootstrapper\October\Config\ConfigInterface;
use Symfony\Component\Process\Exception\RuntimeException;

class ThemeInstaller
{
    protected $config;

    public function __construct(ConfigInterface $config)
    {
        $this->config = $config;
    }

    public function install() {
        $themeDir = getcwd() . '/themes/' . $this->config->theme['name'];
        if ( ! is_dir($themeDir)) {
            mkdir($themeDir);
        }

        if ( ! is_dir($themeDir)) {
            throw new RuntimeException('Could not create theme directory: ' . $themeDir);
        }

        $repo = Repository::open($themeDir);
        try {
            $repo->cloneFrom($this->config->theme['remote'], $themeDir);
        } catch (\RuntimeException $e) {
            throw new \RuntimeException('Error while cloning theme repo: ' . $e->getMessage());
        }
    }
}