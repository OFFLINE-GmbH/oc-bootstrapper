<?php

namespace OFFLINE\Bootstrapper\October\Util;

use RuntimeException;

trait UsesTemplate
{
    /**
     * Look in ~/.composer/october for overwritten template files.
     * Return the default template if there are none.
     *
     * @param $file
     *
     * @return string
     */
    public function getTemplate($file)
    {
        $dist      = __DIR__ . DS . implode(DS, ['..', '..', 'templates', $file]);
        $overwrite = $this->getTemplateOverridePath($file);

        return file_exists($overwrite) ? realpath($overwrite) : realpath($dist);
    }

    /**
     * If the template path contains a git repo update its contents.
     */
    public function updateTemplateFiles()
    {
        $overridePath = $this->getTemplateOverridePath();
        if ( ! is_dir($overridePath . DS . '.git')) {
            return;
        }

        $repo = Git::repo($overridePath);
        try {
            $repo->pull();
        } catch (\Throwable $e) {
            throw new RuntimeException('Error while updating template files: ' . $e->getMessage());
        }
    }

    public function fetchTemplateFiles(string $remote)
    {
        $overridePath = $this->getTemplateOverridePath();
        if ( is_dir($overridePath . DS . '.git')) {
            return $this->updateTemplateFiles();
        }

        if ( ! mkdir($overridePath) && ! is_dir($overridePath)) {
            throw new \RuntimeException(sprintf('Failed to create template directory "%s"', $overridePath));
        }

        try {
            Git::clone($remote, $overridePath);
        } catch (\Throwable $e) {
            throw new RuntimeException('Error while fetching template files: ' . $e->getMessage());
        }
    }

    /**
     * Return the path to the local composer .config path where
     * the template files are stored.
     *
     * @return string
     */
    protected function getTemplateOverridePath($file = null)
    {
        $composerHome = (new Composer())->getHome();

        return implode(DS, [$composerHome, 'october', $file]);
    }
}