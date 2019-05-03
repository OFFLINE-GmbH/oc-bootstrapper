<?php

namespace OFFLINE\Bootstrapper\October\Deployment;

use OFFLINE\Bootstrapper\October\Util\ManageDirectory;
use OFFLINE\Bootstrapper\October\Util\UsesTemplate;

/**
 * Deployment base class
 */
abstract class DeploymentBase
{
    use UsesTemplate, ManageDirectory;
}
