<?php

namespace OFFLINE\Bootstrapper\October\Installer;


use OFFLINE\Bootstrapper\October\Util\Composer;
use OFFLINE\Bootstrapper\October\Util\Git;
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
     * @throws \Symfony\Component\Process\Exception\RuntimeException
     * @throws \Symfony\Component\Process\Exception\LogicException
     * @throws \Symfony\Component\Process\Exception\InvalidArgumentException
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

        $privatePluginInstalled = false;

        foreach ($config as $plugin) {

            $this->write('<info> - ' . $plugin . '</info>');

            list($update, $vendor, $plugin, $remote, $branch) = $this->parse($plugin);

            $vendorDir = $this->createVendorDir($vendor);
            $pluginDir = $vendorDir . DS . $plugin;

            $this->mkdir($pluginDir);

            if ( ! $this->isEmpty($pluginDir)) {
                if ($this->handleExistingPlugin(
                        $vendor,
                        $plugin,
                        $pluginDir,
                        $update
                    ) === false
                ) {
                    continue;
                }
            }

            if ( ! $remote) {
                $this->installViaArtisan($vendor, $plugin);
                continue;
            }

            $repo = Git::repo($pluginDir);
            try {
                $repo->cloneFrom($remote, $pluginDir);
                if ($branch) {
                    $this->write('<comment>   -> ' . sprintf('Checkout "%s" ...', $branch) . '</comment>');
                    $repo->checkout($branch);
                }
            } catch (RuntimeException $e) {
                $this->write('<error> - ' . 'Error while cloning plugin repo: ' . $e->getMessage() . '</error>');
                continue;
            }

            (new Process($this->php . " artisan plugin:refresh {$vendor}.{$plugin}"))->run();

            if ($update === false) {
                $this->gitignore->addPlugin($vendor, $plugin);
            }

            $this->cleanup($pluginDir);
            $privatePluginInstalled = true;
        }

        if ($privatePluginInstalled) {
            $this->write('<info>Installing dependencies of private plugins...</info>');
            (new Composer())->updateLock();
        }

        return true;
    }

    protected function handleExistingPlugin($vendor, $plugin, $pluginDir, $update)
    {
        if ($update === false) {
            $this->write('<comment>   -> ' . sprintf('Plugin "%s.%s" already installed. Skipping.',
                    $vendor, $plugin) . '</comment>');

            return false;
        }

        // Remove any existing local version of the private plugin so it can be checked out via git again
        if ($this->gitignore->hasPluginHeader($vendor, $plugin)) {
            $this->write('<comment>   -> ' . sprintf('Plugin "%s.%s" found in .gitignore. Skipping re-download of newest version ...',
                    $vendor, $plugin) . '</comment>');

            return false;
        }

        $this->write('<comment>   -> ' . sprintf('Removing "%s" to re-download the newest version ...',
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
        // ^Vendor.Plugin (Remote)
        preg_match(
            "/(?<update>\^)?(?<vendor>[^\.]+)\.(?<plugin>[^ #]+)(?: ?\((?<remote>[^\#)]+)(?:#(?<branch>[^\)]+)?)?)?/",
            $plugin,
            $matches
        );

        array_shift($matches);

        $matches = array_map('strtolower', $matches);

        if ($matches['update']) {
            $matches['update'] = true;
        } else {
            $matches['update'] = false;
        }

        return [
            $matches['update'] ?? false,
            $matches['vendor'] ?? '',
            $matches['plugin'] ?? '',
            $matches['remote'] ?? '',
            $matches['branch'] ?? '',
        ];
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