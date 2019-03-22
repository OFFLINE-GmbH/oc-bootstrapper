<?php

namespace OFFLINE\Bootstrapper\October\Manager;

/**
 * Plugin manager class
 */
class ThemeManager extends BaseManager
{
    /**
     * Parse the theme's name and remote path out of the
     * given theme declaration.
     *
     * @param $theme theme declaration like Theme (Remote)
     *
     * @return array array $theme[, remote]
     */
    protected function parseThemeDeclaration(string $theme): array
    {
        preg_match("/([^ ]+)(?: ?\(([^\)]+))?/", $theme, $matches);

        array_shift($matches);

        if (count($matches) < 2) {
            $matches[1] = false;
        }

        return $matches;
    }

    public function removeThemeDir(string $themeDeclaration)
    {
        list($theme, $remote) = $this->parseThemeDeclaration($themeConfig);

        $themeDir = $this->pwd() . DS . implode(DS, ['themes', $theme]);

        $this->rmdir($themeDir);
    }


}