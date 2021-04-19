<?php

namespace OFFLINE\Bootstrapper\October\Deployment;

use OFFLINE\Bootstrapper\October\Config\Yaml;
use OFFLINE\Bootstrapper\October\Util\CliIO;
use OFFLINE\Bootstrapper\October\Util\ManageDirectory;
use OFFLINE\Bootstrapper\October\Util\UsesTemplate;

/**
 * Deployment base class
 */
abstract class DeploymentBase
{
    use UsesTemplate, CliIO, ManageDirectory;

    /**
     * @var Yaml
     */
    protected $config;

    public function __construct(Yaml $config)
    {
        $this->config = $config;
    }
}
