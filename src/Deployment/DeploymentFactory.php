<?php

namespace OFFLINE\Bootstrapper\October\Deployment;

use OFFLINE\Bootstrapper\October\Deployment\Gitlab;
use Symfony\Component\Console\Exception\RuntimeException;
use OFFLINE\Bootstrapper\October\Deployment\DeploymentInterface;

/**
 * Static factory to create deployments
 * @package OFFLINE\Bootstrapper\October\BaseInstaller
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
