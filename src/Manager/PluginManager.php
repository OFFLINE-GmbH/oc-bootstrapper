<?php

namespace OFFLINE\Bootstrapper\October\Manager;

use OFFLINE\Bootstrapper\October\Exceptions\PluginExistsException;
use OFFLINE\Bootstrapper\October\Util\Git;
use RuntimeException;

/**
 * Plugin manager class
 */
class PluginManager extends BaseManager
{

    /**
     * Parse the plugin declaration values out of the given plugin declaration string
     *
     * @param string $pluginDeclaration like ^Vendor.Plugin (Remote)
     *
     * @return array array [bool $update, string $vendor, string $pluginName, string $remote, string $branch]
     */
    public function parseDeclaration(string $pluginDeclaration): array
    {
        preg_match(
            "/(?<update>\^)?(?<vendor>[^\.]+)\.(?<plugin>[^ #]+)(?: ?\((?<remote>[^\#)]+)(?:#(?<branch>[^\)]+)?)?)?/",
            $pluginDeclaration,
            $matches
        );

        array_shift($matches);

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

        return ! $this->isEmpty($pluginDir);
    }

    public function install(string $pluginDeclaration)
    {
        list($update, $vendor, $plugin, $remote, $branch) = $this->parseDeclaration($pluginDeclaration);

        $this->write('- ' . $vendor . '.' . $plugin);

        $pluginDir = $this->createDir($pluginDeclaration);

        if ( ! $this->isEmpty($pluginDir)) {
            throw new PluginExistsException("Plugin %s is already installed.");
        }

        if ($remote === '') {
            return $this->installViaArtisan($pluginDeclaration);
        }

        $repo = Git::repo($pluginDir);
        try {
            $repo->cloneFrom($remote, $pluginDir);
            if ($branch !== '') {
                $this->write('   -> ' . sprintf('Checkout "%s" ...', $branch), 'comment');
                $repo->checkout($branch);
            }
        } catch (RuntimeException $e) {
            throw new RuntimeException('Error while cloning plugin repo: ' . $e->getMessage());
        }

        if(!$this->isWithGitDirectory()) {
            $this->removeGitRepo($this->getDirPath($pluginDeclaration));
        }
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
                    "Error while installing plugin %s via artisan:\n\n%s",
                    $vendor . '.' . $plugin,
                    $e->getMessage()
                )
            );
        }

        return "${vendor}.${plugin} plugin installed";
    }
}
