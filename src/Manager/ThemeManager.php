<?php

namespace OFFLINE\Bootstrapper\October\Manager;

use OFFLINE\Bootstrapper\October\Exceptions\ThemeExistsException;
use OFFLINE\Bootstrapper\October\Util\Git;
use RuntimeException;

/**
 * Plugin manager class
 */
class ThemeManager extends BaseManager
{
    /**
     * Parse the theme's name and remote path out of the
     * given theme declaration.
     *
     * @param $themeDeclaration theme declaration like Theme (Remote)
     *
     * @return array array $theme[, remote]
     */
    public function parseDeclaration(string $themeDeclaration): array
    {
        preg_match("/([^ ]+)(?: ?\(([^\)]+))?/", $themeDeclaration, $matches);

        array_shift($matches);

        if (count($matches) < 2) {
            $matches[1] = false;
        }

        return $matches;
    }

    public function createDir(string $themeDeclaration)
    {
        $themeDir = $this->getDirPath($themeDeclaration);

        if (is_dir($themeDir)) {
            return $themeDir;
        }

        return $this->mkdir($themeDir);
    }

    public function removeDir(string $themeDeclaration)
    {
        $themeDir = $this->getDirPath($themeDeclaration);

        $this->rmdir($themeDir);
    }

    public function getDirPath(string $themeDeclaration)
    {
        list($theme, $remote) = $this->parseDeclaration($themeDeclaration);

        $theme = strtolower($theme);

        $themeDir = $this->pwd() . implode(DS, ['themes', $theme]);

        return $themeDir;
    }

    /**
     * Install a theme via git or artisan.
     *
     * @throws LogicException
     * @throws RuntimeException
     * @throws InvalidArgumentException
     * @throws \RuntimeException
     * @throws \LogicException
     * @throws ThemeExistsException
     */
    public function install(string $themeDeclaration)
    {
        list($theme, $remote) = $this->parseDeclaration($themeDeclaration);

        $themeDir = $this->createDir($themeDeclaration);

        if ( ! $this->isEmpty($themeDir)) {
            throw new ThemeExistsException("-> Theme is already installed. Skipping.");
        }

        if ($remote === false) {
            return $this->installViaArtisan($theme);
        }

        $repo = Git::repo($themeDir);
        try {
            $repo->cloneFrom($remote, $themeDir);
        } catch (RuntimeException $e) {
            throw new RuntimeException('Error while cloning theme repo: ' . $e->getMessage());
        }

        if(!$this->isWithGitDirectory()) {
            $this->removeGitRepo($themeDir);
        }

        return true;
    }

    /**
     * Installs a theme via artisan command.
     *
     * @param string theme declaration string
     *
     * @return string
     * @throws RuntimeException
     */
    public function installViaArtisan(string $themeDeclaration)
    {
        list($theme, $remote) = $this->parseDeclaration($themeDeclaration);

        try {
            $this->artisan->call("theme:install {$theme}");
        } catch (RuntimeException $e) {
            throw new RuntimeException(sprintf('Error while installing theme "%s" via artisan.', $theme));
        }

        return "${theme} theme installed";
    }
}
