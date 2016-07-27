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
    protected $force = false;

    /**
     * Install the deployment setup.
     *
     * @throws \Symfony\Component\Process\Exception\LogicException
     * @throws \Symfony\Component\Process\Exception\RuntimeException
     * @throws \Symfony\Component\Process\Exception\InvalidArgumentException
     */
    public function install($force = false)
    {
        try {
            $deployment = $this->config->git['deployment'];
        } catch (\RuntimeException $e) {
            // Config entry is not set.
            return false;
        }

        // Deployments are disabled
        if ($deployment === false) {
            return true;
        }

        if ( ! method_exists($this, $deployment)) {
            $this->write('<comment>-> Unknown deployment option "' . $deployment . '"</comment>');
            return false;
        }

        $this->force = $force;

        return $this->{$deployment}();
    }

    /**
     * Copy the neccessary tempalte files.
     *
     * @return void
     * @throws \LogicException
     */
    public function gitlab()
    {
        $base = getcwd() . DS;

        if(! $this->force && file_exists($base . '.gitlab-ci.yml')) {
            return $this->write('<comment>-> Deployment is already set up. Use --force to overwrite</comment>');
        }

        copy($this->getTemplate('gitlab-ci.yml'), $base . '.gitlab-ci.yml');
        copy($this->getTemplate('Envoy.blade.php'), $base . 'Envoy.blade.php');
        copy($this->getTemplate('git.cron.sh'), $base . 'git.cron.sh');
    }
}