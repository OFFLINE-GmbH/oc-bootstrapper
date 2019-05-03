<?php

namespace OFFLINE\Bootstrapper\October\Deployment;

use Symfony\Component\Console\Exception\RuntimeException;

/**
 * Static factory to create deployments
 */
final class DeploymentFactory
{
    public static function createDeployment(string $type): DeploymentInterface
    {
        if (strtolower($type) === 'gitlab') {
            return new Gitlab();
        }

        throw new RuntimeException('Unknown deployment type given');
    }
}
