<?php

namespace OFFLINE\Bootstrapper\October\Installer;


use GitElephant\Repository;
use Symfony\Component\Process\Exception\InvalidArgumentException;
use Symfony\Component\Process\Exception\LogicException;
use Symfony\Component\Process\Exception\RuntimeException;
use Symfony\Component\Process\Process;

/**
 * Class ThemeInstaller
 * @package OFFLINE\Bootstrapper\October\BaseInstaller
 */
class ThemeInstaller extends BaseInstaller
{
    /**
     * Install a theme via git or artisan.
     *
     * @throws LogicException
     * @throws RuntimeException
     * @throws InvalidArgumentException
     * @throws \RuntimeException
     * @throws \LogicException
     */
    public function install()
    {
        try {
            $config = $this->config->cms['theme'];
        } catch (\RuntimeException $e) {
            // No theme set
            return false;
        }

        list($theme, $remote) = $this->parse($config);
        if ($remote === false) {
            return $this->installViaArtisan($theme);
        }

        $themeDir = getcwd() . DS . implode(DS, ['themes', $theme]);
        $this->mkdir($themeDir);

        if ( ! $this->isEmpty($themeDir)) {
            $this->write(sprintf('<comment>-> Theme "%s" is already installed. Skipping.</comment>', $theme));
            return;
        }

        $repo = Repository::open($themeDir);
        try {
            $repo->cloneFrom($remote, $themeDir);
        } catch (RuntimeException $e) {
            throw new RuntimeException('Error while cloning theme repo: ' . $e->getMessage());
        }

        $this->cleanup($themeDir);

        return true;
    }

    /**
     * Parse the theme's name and remote path out of the
     * given theme declaration.
     *
     * @param $theme
     *
     * @return mixed
     */
    protected function parse($theme)
    {
        // Theme (Remote)
        preg_match("/([^ ]+)(?: ?\(([^\)]+))?/", $theme, $matches);

        array_shift($matches);

        if (count($matches) < 2) {
            $matches[1] = false;
        }

        return $matches;
    }

    /**
     * Installs a theme via artisan command.
     *
     * @param $theme
     *
     * @return bool
     *
     * @throws LogicException
     * @throws RuntimeException
     */
    protected function installViaArtisan($theme)
    {
        $exitCode = (new Process("php artisan theme:install {$theme}"))->run();

        if ($exitCode !== $this::EXIT_CODE_OK) {
            throw new RuntimeException(sprintf('Error while installing theme "%s" via artisan.', $theme));
        }

        return true;
    }
}