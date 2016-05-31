<?php

namespace OFFLINE\Bootstrapper\October\Installer;


use GitElephant\Repository;
use Symfony\Component\Process\Exception\LogicException;
use Symfony\Component\Process\Exception\RuntimeException;
use Symfony\Component\Process\Process;

/**
 * Class PluginInstaller
 * @package OFFLINE\Bootstrapper\October\BaseInstaller
 */
class PluginInstaller extends BaseInstaller
{
    /**
     * Install a plugin via git or artisan.
     * 
     * @throws \RuntimeException
     * @throws LogicException
     * @throws RuntimeException
     * @throws \Symfony\Component\Process\Exception\InvalidArgumentException
     */
    public function install()
    {
        try {
            $config = $this->config->plugins;
        } catch (\RuntimeException $e) {
            // No plugin set
            return false;
        }

        foreach ($config as $plugin) {
            list($vendor, $plugin, $remote) = $this->parse($plugin);
            $vendor = strtolower($vendor);
            $plugin = strtolower($plugin);

            if ($remote === false) {
                $this->installViaArtisan($vendor, $plugin);
                continue;
            }

            $vendorDir = $this->createVendorDir($vendor);
            $pluginDir = $vendorDir . DS . $plugin;

            $this->mkdir($pluginDir);

            if ( ! $this->isEmpty($pluginDir)) {
                throw new RuntimeException(
                    sprintf('Your plugin directory "%s" is not empty. Cannot clone your repo into it.', $pluginDir)
                );
            }

            $repo = Repository::open($pluginDir);
            try {
                $repo->cloneFrom($remote, $pluginDir);
            } catch (RuntimeException $e) {
                throw new RuntimeException('Error while cloning plugin repo: ' . $e->getMessage());
            }

            (new Process("php artisan plugin:refresh {$vendor}.{$plugin}"))->run();

            $this->cleanup($pluginDir);
        }

        return true;
    }

    /**
     * Parse the Vendor, Plugin and Remote values out of the
     * given plugin declaration.
     *
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
     * Create the plugin's vendor directory.
     *
     * @param $vendor
     *
     * @return string
     * @throws \RuntimeException
     */
    protected function createVendorDir($vendor)
    {
        $pluginDir = getcwd() . DS . implode(DS, ['plugins', $vendor]);

        return $this->mkdir($pluginDir);
    }

    /**
     * Installs a plugin via artisan command.
     *
     * @param $vendor
     * @param $plugin
     *
     * @throws LogicException
     * @throws RuntimeException
     */
    protected function installViaArtisan($vendor, $plugin)
    {
        $exitCode = (new Process("php artisan plugin:install {$vendor}.{$plugin}"))->run();

        if ($exitCode !== $this::EXIT_CODE_OK) {
            throw new RuntimeException(
                sprintf('Error while installing plugin %s via artisan. Is your database set up correctly?',
                    $vendor . '.' . $plugin
                )
            );
        }
    }
}