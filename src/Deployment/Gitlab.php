<?php

namespace OFFLINE\Bootstrapper\October\Deployment;

use OFFLINE\Bootstrapper\October\Exceptions\DeploymentExistsException;

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
        if ( ! $force && $this->fileExists('.gitlab-ci.yml')) {
            throw new DeploymentExistsException('-> Deployment is already set up. Use --force to overwrite');
        }

        $this->copy($this->getTemplate('gitlab-ci.yml'), '.gitlab-ci.yml');
        $this->copy($this->getTemplate('Envoy.blade.php'), 'Envoy.blade.php');

        $this->replaceVars('.gitlab-ci.yml', $this->config->toArray());
        $this->replaceVars('Envoy.blade.php', $this->config->toArray());
    }
}
