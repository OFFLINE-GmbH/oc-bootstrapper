<?php

namespace OFFLINE\Bootstrapper\October\Deployment;

use OFFLINE\Bootstrapper\October\Config\Yaml;
use Symfony\Component\Console\Exception\RuntimeException;

/**
 * Static factory to create deployments
 */
final class DeploymentFactory
{
    public static function createDeployment(string $type, Yaml $config): DeploymentInterface
    {
        if (strtolower($type) === 'gitlab') {
            return new Gitlab($config);
        }

        throw new RuntimeException('Unknown deployment type given');
    }
}
