<?php

namespace OFFLINE\Bootstrapper\October\Manager;

use OFFLINE\Bootstrapper\October\Util\Artisan;
use OFFLINE\Bootstrapper\October\Util\Git;

/**
 * Plugin manager class
 */
class PluginManager extends BaseManager
{

   /**
     * Parse the Vendor, Plugin and Remote values out of the
     * given plugin declaration.
     *
     * @param string $pluginDeclaration like Vendor.Plugin (Remote)
     *
     * @return array array containing $vendor, $pluginName[, $remote[, $branch]]
     */
    public function parseDeclaration(string $pluginDeclaration): array
    {
        preg_match("/([^\.]+)\.([^ #]+)(?: ?\(([^\#)]+)(?:#([^\)]+)?)?)?/", $pluginDeclaration, $matches);

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
    public function createVendorDir($vendor)
    {
        $vendor = strtolower($vendor);

        $vendorDir = $this->pwd() . implode(DS, ['plugins', $vendor]);

        if (is_dir($vendorDir)) {
            return $vendorDir;
        }

        return $this->mkdir($vendorDir);
    }

    public function createDir(string $pluginDeclaration)
    {
        list($update, $vendor, $plugin, $remote, $branch) = $this->parseDeclaration($pluginDeclaration);

        $vendor = strtolower($vendor);
        $plugin = strtolower($plugin);

        $vendorDir = $this->createVendorDir($vendor);

        $pluginDir = $this->getDirPath($pluginDeclaration);

        if (is_dir($pluginDir)) {
            return $pluginDir;
        }

        return $this->mkdir($pluginDir);
    }

    public function removeDir(string $pluginDeclaration)
    {
        list($update, $vendor, $plugin, $remote, $branch) = $this->parseDeclaration($pluginDeclaration);
        $vendor = strtolower($vendor);
        $plugin = strtolower($plugin);

        $pluginDir = $this->pwd() . implode(DS, ['plugins', $vendor, $plugin]);

        $this->rmdir($pluginDir);
    }

    public function getDirPath(string $pluginDeclaration)
    {
        list($update, $vendor, $plugin, $remote, $branch) = $this->parseDeclaration($pluginDeclaration);

        $vendor = strtolower($vendor);
        $plugin = strtolower($plugin);

        $pluginDir = $this->pwd() . implode(DS, ['plugins', $vendor, $plugin]);

        return $pluginDir;
    }

    public function isInstalled(string $pluginDeclaration)
    {
        $pluginDir = $this->getDirPath($pluginDeclaration);

        return $this->isEmpty($pluginDir);
    }

    public function install(string $pluginDeclaration)
    {
        list($update, $vendor, $plugin, $remote, $branch) = $this->parseDeclaration($pluginDeclaration);

        $this->write('- ' . $vendor . '.' . $plugin);

        $pluginDir = $this->createDir($pluginDeclaration);

        if (!$this->isEmpty($pluginDir)) {
            throw new RuntimeException("Plugin directory not empty. Aborting.");
        }

        if ($remote === false) {
            return $this->installViaArtisan($pluginDeclaration);
        }

        $repo = Git::repo($pluginDir);
        try {
            $repo->cloneFrom($remote, $pluginDir);
            if ($branch !== false) {
                $this->write('   -> ' . sprintf('Checkout "%s" ...', $branch), 'comment');
                $repo->checkout($branch);
            }
        } catch (RuntimeException $e) {
            throw new RuntimeException('Error while cloning plugin repo: ' . $e->getMessage());
        }

        $this->artisan->call("plugin:refresh {$vendor}.{$plugin}");

        if ($update === false) {
            $this->gitignore->addPlugin($vendor, $plugin);
        }

        $this->removeGitRepo($this->getDirPath($pluginDeclaration));
    }

    /**
     * Installs a plugin via artisan command.
     *
     * @param string plugin declaration string
     *
     * @return string
     * @throws RuntimeException
     */
    public function installViaArtisan(string $pluginDeclaration)
    {
        list($update, $vendor, $plugin, $remote, $branch) = $this->parseDeclaration($pluginDeclaration);

        try {
            $this->artisan->call("plugin:install {$vendor}.{$plugin}");
        } catch (RuntimeException $e) {
            throw new RuntimeException(
                sprintf(
                    'Error while installing plugin %s via artisan. Is your database set up correctly?',
                    $vendor . '.' . $plugin
                )
            );
        }

        return "${vendor}.${plugin} plugin installed";
    }
}
