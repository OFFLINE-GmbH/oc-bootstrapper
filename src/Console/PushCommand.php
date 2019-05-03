<?php

namespace OFFLINE\Bootstrapper\October\Console;

use RuntimeException;
use OFFLINE\Bootstrapper\October\Util\Git;
use OFFLINE\Bootstrapper\October\Util\CliIO;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use OFFLINE\Bootstrapper\October\Util\ManageDirectory;
use Symfony\Component\Console\Exception\InvalidArgumentException;

/**
 * Class PushCommand
 * @package OFFLINE\Bootstrapper\October\Console
 */
class PushCommand extends Command
{
    use ManageDirectory, CliIO;

    /**
     * Configure the command options.
     *
     * @throws InvalidArgumentException
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('push')
            ->setDescription('Push project repository to origin master')
            ->addArgument('directory', InputArgument::OPTIONAL, 'Name of the working directory', '.');
    }

    /**
     * Execute the command.
     *
     * @param  InputInterface  $input
     * @param  OutputInterface $output
     *
     * @throws RuntimeException
     * @return mixed
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setOutput($output);

        $this->write('Pushing the project repo to origin master...');

        try {
            $repo = Git::repo($this->pwd());
            $status = $repo->getStatus();
        } catch (Throwable $e) {
            $this->write($e->getMessage(), 'error');
            return false;
        }

        if (count($status->all()) === 0) {
            $this->write('Nothing to push');
            return true;
        }

        $repo->stage();

        $repo->commit('[ci skip] Added changes from ' . gethostname());

        $repo->push();

        $this->write('Pushed');

        return true;
    }
}
