<?php

namespace OFFLINE\Bootstrapper\October\Deployment;

use Symfony\Component\Process\Exception\LogicException;
use Symfony\Component\Process\Exception\RuntimeException;
use Symfony\Component\Process\Exception\InvalidArgumentException;

/**
 * Deployment interface
 */
interface Deployment
{
    /**
     * Install the deployment setup
     *
     * @param boolean $force parameter to enforce installing even if installed
     * @throws LogicException
     * @throws RuntimeException
     * @throws InvalidArgumentException
     * @return void
     */
    public function install($force = false);
}
