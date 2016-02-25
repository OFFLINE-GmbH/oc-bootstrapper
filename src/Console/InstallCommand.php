<?php

namespace OFFLINE\Bootstrapper\October\Console;

use OFFLINE\Bootstrapper\October\Config\Yaml;
use OFFLINE\Bootstrapper\October\Util\ConfigWriter;
use OFFLINE\Bootstrapper\October\Util\KeyGenerator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\LogicException;
use Symfony\Component\Process\Process;
use ZipArchive;

class InstallCommand extends Command
{
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
//        if ( ! file_exists($configFile)) {
//            return $output->writeln('<comment>october.yaml not found. Run october init first.</comment>');
//        }
//
//        $output->writeln('<info>Downloading latest October CMS...</info>');
//        (new OctoberCms())->download();
//
//        $output->writeln('<info>Installing composer dependencies...</info>');
//        (new Composer())->install();

        $output->writeln('<info>Setting up config files...</info>');
        $this->writeConfig($configFile);

        $output->writeln('<info>Migrating Database...</info>');
        (new Process('php artisan october:up'))->run();

        $output->writeln('<comment>Application ready! Build something amazing.</comment>');
    }

    /**
     * @param $configFile
     */
    protected function writeConfig($configFile)
    {
        $yaml = new Yaml($configFile);

        $config = new ConfigWriter();
        $config->setEnvironment('dev')
               ->setAppKey((new KeyGenerator())->generate());

        // DB CONFIG
        $dbConnection = $yaml->database['connection'];
        $values       = ['default' => $dbConnection];

        foreach ($yaml->database as $setting) {
            $values["connections.{$dbConnection}.database"] = $setting;
        }

        $config->write('database', $values);
    }

}