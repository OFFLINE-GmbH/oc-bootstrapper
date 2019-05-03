<?php

namespace OFFLINE\Bootstrapper\October\Deployment;

use OFFLINE\Bootstrapper\October\Deployment\DeploymentBase;
use OFFLINE\Bootstrapper\October\Deployment\DeploymentInterface;

/**
 * GitLab deployment
 */
class Gitlab extends DeploymentBase implements DeploymentInterface
{
    /**
     * @inheritDoc
     */
    public function install($force = false)
    {
        if (! $this->force && $this->fileExists('.gitlab-ci.yml')) {
            return $this->write('<comment>-> Deployment is already set up. Use --force to overwrite</comment>');
        }

        $this->copy($this->getTemplate('gitlab-ci.yml'), '.gitlab-ci.yml');
        $this->copy($this->getTemplate('Envoy.blade.php'), 'Envoy.blade.php');
        $this->copy($this->getTemplate('git.cron.sh'), 'git.cron.sh');
    }
}
