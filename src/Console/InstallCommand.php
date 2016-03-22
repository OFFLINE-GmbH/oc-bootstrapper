<?php

namespace OFFLINE\Bootstrapper\October\Console;

use OFFLINE\Bootstrapper\October\Config\Setup;
use OFFLINE\Bootstrapper\October\Config\Yaml;
use OFFLINE\Bootstrapper\October\Downloader\OctoberCms;
use OFFLINE\Bootstrapper\October\Installer\PluginInstaller;
use OFFLINE\Bootstrapper\October\Installer\ThemeInstaller;
use OFFLINE\Bootstrapper\October\Util\Composer;
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
    /**
     * @var
     */
    public $config;

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
     * @throws RuntimeException
     * @throws LogicException
     * @throws \Symfony\Component\Process\Exception\RuntimeException
     * @return mixed
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ( ! class_exists('ZipArchive')) {
            throw new RuntimeException('The Zip PHP extension is not installed. Please install it and try again.');
        }

        $configFile = getcwd() . '/october.yaml';
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
        (new Process('php artisan october:up'))->run();

        $output->writeln('<info>Removing demo data...</info>');
        (new Process('php artisan october:fresh'))->run();

        $output->writeln('<info>Clearing cache...</info>');
        (new Process('php artisan clear-compiled'))->run();
        (new Process('php artisan cache:clear'))->run();

        $output->writeln('<info>Installing Theme...</info>');
        try {
            (new ThemeInstaller($this->config))->install();
        } catch (\RuntimeException $e) {
            $output->writeln("<error>${e}</error>");
        }

        $output->writeln('<info>Installing Plugins...</info>');
        try {
            (new PluginInstaller($this->config))->install();
        } catch (\RuntimeException $e) {
            $output->writeln("<error>${e}</error>");
        }

        $output->writeLn('<info>Creating .gitignore</info>');
        $this->gitignore();

        $output->writeln('<comment>Application ready! Build something amazing.</comment>');
    }

    /**
     *
     */
    protected function writeConfig()
    {
        $setup = new Setup($this->config);

        $setup
            ->devEnvironment()
            ->prodEnvironment();
    }

    /**
     *
     */
    protected function gitignore()
    {
        file_put_contents(getcwd() . '/.gitignore', implode("\n", [
                '.DS_Store',
                '*.log',
                '*node_modules*',
                '.idea',
                '*.sass-cache*',
                'vendor',
            ])
        );
    }
}