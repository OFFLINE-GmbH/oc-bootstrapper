<?php

namespace OFFLINE\Bootstrapper\October\Console;

use OFFLINE\Bootstrapper\October\Config\Setup;
use OFFLINE\Bootstrapper\October\Config\Yaml;
use OFFLINE\Bootstrapper\October\Downloader\OctoberCms;
use OFFLINE\Bootstrapper\October\Installer\DeploymentInstaller;
use OFFLINE\Bootstrapper\October\Installer\PluginInstaller;
use OFFLINE\Bootstrapper\October\Installer\ThemeInstaller;
use OFFLINE\Bootstrapper\October\Util\Composer;
use OFFLINE\Bootstrapper\October\Util\UsesTemplate;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\LogicException;
use Symfony\Component\Process\Process;
use ZipArchive;

/**
 * Class InstallCommand
 * @package OFFLINE\Bootstrapper\October\Console
 */
class InstallCommand extends Command
{
    use UsesTemplate;

    /**
     * Exit code for processes
     */
    const EXIT_CODE_OK = 0;

    /**
     * @var
     */
    public $config;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * Configure the command options.
     *
     * @throws InvalidArgumentException
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('install')
            ->setDescription('Install October CMS.');
    }

    /**
     * Execute the command.
     *
     * @param  InputInterface  $input
     * @param  OutputInterface $output
     *
     * @return mixed
     * @throws \Symfony\Component\Process\Exception\RuntimeException
     * @throws \Symfony\Component\Process\Exception\InvalidArgumentException
     * @throws LogicException
     * @throws RuntimeException
     * @throws InvalidArgumentException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ( ! class_exists('ZipArchive')) {
            throw new RuntimeException('The Zip PHP extension is not installed. Please install it and try again.');
        }

        $this->output = $output;

        $configFile = getcwd() . DS . 'october.yaml';
        if ( ! file_exists($configFile)) {
            return $output->writeln('<comment>october.yaml not found. Run october init first.</comment>');
        }

        $this->config = new Yaml($configFile);

        $output->writeln('<info>Downloading latest October CMS...</info>');
        (new OctoberCms())->download();

        $output->writeln('<info>Installing composer dependencies...</info>');
        (new Composer())->install();

        $output->writeln('<info>Setting up config files...</info>');
        $this->writeConfig();

        $output->writeln('<info>Migrating Database...</info>');
        $this->runProcess('php artisan october:up', 'Migrations failed!');

        $output->writeln('<info>Removing demo data...</info>');
        $this->runProcess('php artisan october:fresh', 'Failed to remove demo data!');

        $output->writeln('<info>Clearing cache...</info>');
        $this->runProcess('php artisan clear-compiled', 'Failed to clear compiled files!');
        $this->runProcess('php artisan cache:clear', 'Failed to clear cache!');

        $output->writeln('<info>Installing Theme...</info>');
        try {
            (new ThemeInstaller($this->config))->install();
        } catch (\RuntimeException $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
        }

        $output->writeln('<info>Installing Plugins...</info>');
        try {
            (new PluginInstaller($this->config))->install();
        } catch (\RuntimeException $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
        }

        $output->writeln('<info>Setting up deployments...</info>');
        try {
            (new DeploymentInstaller($this->config))->install();
        } catch (\RuntimeException $e) {
            $output->writeln("<error>${e}</error>");
        }

        $output->writeln('<info>Creating .gitignore...</info>');
        $this->gitignore();

        $output->writeln('<info>Creating README...</info>');
        $this->readme();

        $output->writeln('<info>Cleaning up...</info>');
        $this->cleanup();

        $output->writeln('<comment>Application ready! Build something amazing.</comment>');

        return true;
    }

    /**
     * Create the .env and config files.
     * 
     * @return void
     */
    protected function writeConfig()
    {
        $setup = new Setup($this->config);

        $setup->env()->config();
    }

    /**
     * Copy the .gitignore template.
     * 
     * @return void
     */
    protected function gitignore()
    {
        $template = $this->getTemplate('gitignore');
        copy($template, getcwd() . DS . '.gitignore');
    }

    /**
     * Copy the README template.
     *
     * @return void
     */
    protected function readme()
    {
        $template = $this->getTemplate('README.md');
        copy($template, getcwd() . DS . 'README.md');
    }

    protected function cleanup()
    {
        $remove = ['CONTRIBUTING.md', 'CHANGELOG.md'];
        foreach ($remove as $file) {
            @unlink(getcwd() . DS . $file);
        }
    }

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
        $exitCode = (new Process($command))->run();

        return $this->checkResult($exitCode, $errorMessage);
    }

    /**
     * Checks the result of a process.
     *
     * @param $exitCode
     * @param $message
     *
     * @return bool
     */
    protected function checkResult($exitCode, $message)
    {
        if ($exitCode !== $this::EXIT_CODE_OK) {
            $this->output->writeln('<error>' . $message . '</error>');

            return false;
        }

        return true;
    }
}
