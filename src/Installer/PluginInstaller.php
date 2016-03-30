<?php

namespace OFFLINE\Bootstrapper\October\Installer;


use GitElephant\Repository;
use OFFLINE\Bootstrapper\October\Config\Config;
use Symfony\Component\Process\Exception\RuntimeException;
use Symfony\Component\Process\Process;

/**
 * Class PluginInstaller
 * @package OFFLINE\Bootstrapper\October\Installer
 */
class PluginInstaller
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * PluginInstaller constructor.
     *
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     *
     */
    public function install()
    {

        foreach ($this->config->plugins as $plugin) {

            list($vendor, $plugin, $remote) = $this->parse($plugin);
            $vendor = strtolower($vendor);
            $plugin = strtolower($plugin);

            if ($remote === false) {
                (new Process("php artisan plugin:install {$vendor}.{$plugin}"))->run();
                continue;
            }

            $vendorDir = $this->createVendorDir($vendor);
            $pluginDir = $vendorDir . DS . $plugin;

            $this->mkdir($pluginDir);

            $repo = Repository::open($pluginDir);
            try {
                $repo->cloneFrom($remote, $pluginDir);
            } catch (\RuntimeException $e) {
                throw new \RuntimeException('Error while cloning plugin repo: ' . $e->getMessage());
            }

            (new Process("php artisan plugin:refresh {$vendor}.{$plugin}"))->run();
        }
    }

    /**
     * @param $plugin
     *
     * @return mixed
     */
    protected function parse($plugin)
    {
        // Vendor.Plugin (Remote)
        preg_match("/([^\.]+)\.([^ ]+)(?: ?\(([^\)]+))?/", $plugin, $matches);

        array_shift($matches);

        if (count($matches) < 3) {
            $matches[2] = false;
        }

        return $matches;
    }

    /**
     * @param $vendor
     *
     * @return string
     */
    protected function createVendorDir($vendor)
    {
        $pluginDir = getcwd() . DS . implode(DS, ['plugins', $vendor]);

        return $this->mkdir($pluginDir);
    }

    /**
     * @param $dir
     */
    protected function mkdir($dir)
    {
        if ( ! is_dir($dir)) {
            mkdir($dir);
        }

        if ( ! is_dir($dir)) {
            throw new RuntimeException('Could not create plugin directory: ' . $dir);
        }

        return $dir;
    }
}