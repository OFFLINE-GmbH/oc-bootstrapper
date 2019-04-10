<?php

namespace OFFLINE\Bootstrapper\October\Console;

use OFFLINE\Bootstrapper\October\Installer\PluginInstaller;
use OFFLINE\Bootstrapper\October\Manager\PluginManager;
use OFFLINE\Bootstrapper\October\Util\Artisan;
use OFFLINE\Bootstrapper\October\Util\Composer;
use OFFLINE\Bootstrapper\October\Util\Gitignore;
use OFFLINE\Bootstrapper\October\Util\RunsProcess;
use OFFLINE\Bootstrapper\October\Util\ConfigMaker;
use OFFLINE\Bootstrapper\October\Util\CliIO;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\LogicException;

/**
 * Class UpdateCommand
 * @package OFFLINE\Bootstrapper\October\Console
 */
class UpdateCommand extends Command
{
    use ConfigMaker, RunsProcess, CliIO;

    /**
     * @var Artisan
     */
    protected $artisan;

    /**
     * @var Composer
     */
    protected $composer;

    /**
     * @var PluginManager
     */
    protected $pluginManager;

    /**
     * @var Gitignore
     */
    protected $gitignore;

    /**
     * @var string
     */
    protected $php;

    /**
     * @inheritdoc
     */
    public function __construct($name = null)
    {
        $this->pluginManager = new PluginManager();
        $this->artisan       = new Artisan();
        $this->composer      = new Composer();

        $this->setPhp();

        parent::__construct($name);
    }

    /**
     * Set PHP version to be used in console commands
     */
    public function setPhp(string $php = 'php')
    {
        //IDEA: simple observer for changing the php version
        $this->php = $php;
        $this->artisan->setPhp($php);
        $this->pluginManager->setPhp($php);
    }

    /**
     * Configure the command options.
     *
     * @return void
     * @throws InvalidArgumentException
     */
    protected function configure()
    {
        $this
            ->setName('update')
            ->setDescription('Update October CMS.')
            ->addOption(
                'php',
                null,
                InputOption::VALUE_OPTIONAL,
                'Specify the path to a custom PHP binary',
                'php'
            );
    }

    /**
     * Execute the command.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
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
        $this->prepareEnv($input, $output);

        try {
            $this->makeConfig();
        } catch (RuntimeException $e) {
            return $this->write($e->getMessage());
        }

        if ( ! empty($php = $input->getOption('php'))) {
            $this->setPhp($php);
        }

        // 1. Run `october install` command, which will install all plugins and themes that are not installed yet

        $this->write('<info>Installing new plugins</info>');
        $this->gitignore = new Gitignore(getcwd() . DS . '.gitignore');

        try {
            (new PluginInstaller($this->config, $this->gitignore, $this->output, $this->php))->install();
        } catch (\RuntimeException $e) {
            $output->writeln('<comment>' . $e->getMessage() . '</comment>');
        }

        // 2. Remove every plugin that has git repo specified in october.yaml, since the  `october:update`
        // will not be able to update them.
        $pluginsConfigs = $this->config->plugins;

        $this->write('<info>Removing private plugins</info>');
        foreach ($pluginsConfigs as $pluginConfig) {
            list($vendor, $plugin, $remote, $branch) = $this->pluginManager->parseDeclaration($pluginConfig);

            if ( ! empty($remote)) {
                $this->pluginManager->removeDir($pluginConfig);
            }
        }

        $this->write('<info>Cleared private plugins</info>');

        // 3. Run `php artisan october:update`, which updates core and marketplace plugins
        $this->write('<info>Running artisan october:update</info>');
        $this->artisan->call('october:update');

        // 4. Git clone all plugins again
        $this->write('<info>Reinstalling plugins:</info>');

        foreach ($pluginsConfigs as $pluginConfig) {
            list($vendor, $plugin, $remote, $branch) = $this->pluginManager->parseDeclaration($pluginConfig);

            if ( ! empty($remote)) {
                $this->pluginManager->install($pluginConfig);
            }
        }

        // 5. Run `php artisan october:up` to migrate all versions of plugins
        $this->write('<info>Migrating all unmigrated versions</info>');

        $this->artisan->call('october:up');

        // 6. Run `composer update` to update all composer packages
        $this->write('<info>Running composer update</info>');
        $this->composer->updateLock();

        return true;
    }

    /**
     * Prepare the environment
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return void
     */
    protected function prepareEnv(InputInterface $input, OutputInterface $output)
    {
        $this->setOutput($output);
        $this->pluginManager->setOutput($output);
    }
}
