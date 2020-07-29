<?php

namespace OFFLINE\Bootstrapper\October\Util;

use LogicException;
use RuntimeException;
use Symfony\Component\Process\Process;

trait RunsProcess
{
    /**
     * Runs a process and checks it's result.
     * Prints an error message if necessary.
     *
     * @param $command
     * @param $errorMessage
     *
     * @return bool
     * @throws RuntimeException
     * @throws LogicException
     */
    protected function runProcess($command, $errorMessage, $timeout = 30)
    {
        $process = new Process($command);
        $process->setTimeout($timeout);
        $process->enableOutput();
        $exitCode = $process->run();

        return $this->checkProcessResult($exitCode, $errorMessage, $process->getOutput());
    }

    protected function runProcessWithOutput($command, $errorMessage, $timeout = 30)
    {
        $process = new Process($command);
        $process->setTimeout($timeout);
        $process->enableOutput();
        $exitCode = $process->run();

        $this->checkProcessResult($exitCode, $errorMessage, $process->getOutput());

        return $process->getOutput();
    }

    /**
     * Checks the result of a process.
     *
     * @param $exitCode
     * @param $message
     *
     * @param $output
     *
     * @return bool
     */
    protected function checkProcessResult($exitCode, $message, $output)
    {
        if ($exitCode !== 0) {
            $this->output->writeln('<error>' . $message . ': ' . $output . '</error>');

            return false;
        }

        return true;
    }
}