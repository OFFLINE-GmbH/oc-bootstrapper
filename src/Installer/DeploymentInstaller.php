<?php

namespace OFFLINE\Bootstrapper\October\Installer;


use OFFLINE\Bootstrapper\October\Util\UsesTemplate;

/**
 * Class DeploymentInstaller
 * @package OFFLINE\Bootstrapper\October\BaseInstaller
 */
class DeploymentInstaller extends BaseInstaller
{
    use UsesTemplate;

    /**
     * Install the deployment setup.
     *
     * @throws \Symfony\Component\Process\Exception\LogicException
     * @throws \Symfony\Component\Process\Exception\RuntimeException
     * @throws \Symfony\Component\Process\Exception\InvalidArgumentException
     */
    public function install()
    {
        try {
            $deployment = $this->config->deployment;
        } catch (\RuntimeException $e) {
            // Config entry is not set.
            return false;
        }

        if ( ! method_exists($this, $deployment)) {
            return false;
        }

        return $this->{$deployment}();
    }

    /**
     * Copy the neccessary tempalte files.
     *
     * @return void
     */
    public function gitlab()
    {
        copy($this->getTemplate('gitlab-ci.yml'), getcwd() . DS . '.gitlab-ci.yml');
        copy($this->getTemplate('Envoy.blade.php'), getcwd() . DS . 'Envoy.blade.php');
        copy($this->getTemplate('git.cron.sh'), getcwd() . DS . 'git.cron.sh');
    }
}