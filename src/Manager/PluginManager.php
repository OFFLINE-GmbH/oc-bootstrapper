<?php

namespace OFFLINE\Bootstrapper\October\Manager;

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
    public function parsePluginDeclaration(string $pluginDeclaration): array
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
        $pluginDir = $this->pwd() . implode(DS, ['plugins', $vendor]);

        return $this->mkdir($pluginDir);
    }

    public function removePluginDir(string $pluginDeclaration)
    {
        list($vendor, $plugin, $remote, $branch) = $this->parsePluginDeclaration($pluginDeclaration);
        $vendor = strtolower($vendor);
        $plugin = strtolower($plugin);

        $pluginDir = $this->pwd() . implode(DS, ['plugins', $vendor, $plugin]);

        $this->rmdir($pluginDir);
    }

    public function getPluginDir(string $pluginDeclaration)
    {
        list($vendor, $plugin, $remote, $branch) = $this->parsePluginDeclaration($pluginDeclaration);
        $pluginDir = $this->pwd() . implode(DS, ['plugins', $vendor, $plugin]);
        return $pluginDir;
    }

}