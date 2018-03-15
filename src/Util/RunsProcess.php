<?php

namespace OFFLINE\Bootstrapper\October\Util;

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
     * @throws \Symfony\Component\Process\Exception\RuntimeException
     * @throws \Symfony\Component\Process\Exception\LogicException
     */
    protected function runProcess($command, $errorMessage)
    {

        $process = (new Process($command));
        $exitCode = $process->run();

        return $this->checkProcessResult($exitCode, $errorMessage);
    }

    /**
     * Checks the result of a process.
     *
     * @param $exitCode
     * @param $message
     *
     * @return bool
     */
    protected function checkProcessResult($exitCode, $message)
    {
        if ($exitCode !== 0) {
            $this->output->writeln('<error>' . $message . '</error>');

            return false;
        }

        return true;
    }
}