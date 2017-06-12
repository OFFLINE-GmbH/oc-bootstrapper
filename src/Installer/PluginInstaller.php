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
     * @return bool
     */
    public function install()
    {
        try {
            $config = $this->config->plugins;
        } catch (\RuntimeException $e) {
            $this->write('<info> - Nothing to install</info>');

            // No plugin set
            return false;
        }

        $isBare = isset($this->config->git['bareRepo'])
            ? (bool)$this->config->git['bareRepo']
            : false;
        
        $excludePlugins = isset($this->config->git['excludePlugins'])
            ? (bool)$this->config->git['excludePlugins']
            : false;

        foreach ($config as $plugin) {

            $this->write('<info> - ' . $plugin . '</info>');

            list($vendor, $plugin, $remote, $branch) = $this->parse($plugin);
            $vendor = strtolower($vendor);
            $plugin = strtolower($plugin);

            $vendorDir = $this->createVendorDir($vendor);
            $pluginDir = $vendorDir . DS . $plugin;

            $this->mkdir($pluginDir);

            if ( ! $this->isEmpty($pluginDir)) {

                if ($this->handleExistingPlugin($excludePlugins, $remote, $vendor, $plugin, $pluginDir,
                        $isBare) === false
                ) {
                    continue;
                }
            }

            if ($remote === false) {
                $this->installViaArtisan($vendor, $plugin);
                continue;
            }

            $repo = Repository::open($pluginDir);
            try {
                $repo->cloneFrom($remote, $pluginDir);
                if ($branch !== false) {
                    $this->write('<comment>   -> ' . sprintf('Checkout "%s" ...', $branch) . '</comment>');
                    $repo->checkout($branch);
                }
            } catch (RuntimeException $e) {
                $this->write('<error> - ' . 'Error while cloning plugin repo: ' . $e->getMessage() . '</error>');
                continue;
            }

            if ($excludePlugins === false) {
                (new Process("php artisan plugin:refresh {$vendor}.{$plugin}"))->run();

                if ($isBare) {
                    $this->gitignore->addPlugin($vendor, $plugin);
                }
            }

            $this->cleanup($pluginDir);
        }

        return true;
    }

    protected function handleExistingPlugin($excludePlugins, $remote, $vendor, $plugin, $pluginDir, $isBare)
    {
        if ($excludePlugins === false || $remote === false || $isBare === false) {
            $this->write('<comment>   -> ' . sprintf('Plugin "%s.%s" already installed. Skipping.',
                    $vendor, $plugin) . '</comment>');

            return false;
        }

        // Remove any existing local version of the private plugin so it can be checked out via git again
        if ($this->gitignore->hasPluginHeader($vendor, $plugin)) {
            $this->write('<comment>   -> ' . sprintf('Plugin "%s.%s" found in .gitignore. Skipping redownload of newest version ...',
                    $vendor, $plugin) . '</comment>');

            return false;
        }

        $this->write('<comment>   -> ' . sprintf('Removing "%s" to redownload the newest version ...',
                $pluginDir) . '</comment>');

        $this->rmdir($pluginDir);
        $this->mkdir($pluginDir);

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
        preg_match("/([^\.]+)\.([^ #]+)(?: ?\(([^\#)]+)(?:#([^\)]+)?)?)?/", $plugin, $matches);

        array_shift($matches);

        if (count($matches) < 3) {
            $matches[2] = false;
        }

        if (count($matches) < 4) {
            $matches[3] = false;
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