<?php

namespace OFFLINE\Bootstrapper\October\Deployment;

use OFFLINE\Bootstrapper\October\Util\UsesTemplate;
use OFFLINE\Bootstrapper\October\Util\ManageDirectory;
use OFFLINE\Bootstrapper\October\Deployment\DeploymentInterface;

/**
 * Deployment base class
 */
abstract class DeploymentBase
{
    use UsesTemplate, ManageDirectory;
}
