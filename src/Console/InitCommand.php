<?php

namespace OFFLINE\Bootstrapper\October\Console;

use InvalidArgumentException;
use OFFLINE\Bootstrapper\October\Util\ManageDirectory;
use OFFLINE\Bootstrapper\October\Util\UsesTemplate;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class InitCommand
 * @package OFFLINE\Bootstrapper\October\Console
 */
class InitCommand extends Command
{
    use UsesTemplate, ManageDirectory;

    /**
     * Configure the command options.
     *
     * @return void
     * @throws InvalidArgumentException
     */
    protected function configure()
    {
        $this
            ->setName('init')
            ->setDescription('Create a new October CMS project.')
            ->addArgument('directory', InputArgument::OPTIONAL, 'Name of the working directory', '.');
    }

    /**
     * Execute the command.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return mixed
     * @throws RuntimeException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<info>Creating project directory...</info>');

        $dir = $this->pwd() . $input->getArgument('directory');

        $this->mkdir($dir);

        $output->writeln('<info>Updating template files...</info>');
        $this->updateTemplateFiles();

        $template = $this->getTemplate('october.yaml');
        $target   = $dir . DS . 'october.yaml';

        $output->writeln('<info>Creating default october.yaml...</info>');

        if ($this->fileExists($target)) {
            return $output->writeln('<comment>october.yaml already exists: ' . $target . '</comment>');
        }

        $this->copy($template, $target);

        $output->writeln('<comment>Done! Now edit your october.yaml and run october install.</comment>');

        return true;
    }
}
