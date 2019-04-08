<?php

namespace OFFLINE\Bootstrapper\October\Console;

use OFFLINE\Bootstrapper\October\Manager\PluginManager;
use OFFLINE\Bootstrapper\October\Manager\ThemeManager;
use OFFLINE\Bootstrapper\October\Config\Setup;
use OFFLINE\Bootstrapper\October\Downloader\OctoberCms;
use OFFLINE\Bootstrapper\October\Installer\DeploymentInstaller;
use OFFLINE\Bootstrapper\October\Util\Artisan;
use OFFLINE\Bootstrapper\October\Util\Composer;
use OFFLINE\Bootstrapper\October\Util\Gitignore;
use OFFLINE\Bootstrapper\October\Util\UsesTemplate;
use OFFLINE\Bootstrapper\October\Util\ManageDirectory;
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
 * Class InstallCommand
 * @package OFFLINE\Bootstrapper\October\Console
 */
class InstallCommand extends Command
{
    use ConfigMaker, UsesTemplate, CliIO, ManageDirectory;
    
    /**
    * @var Gitignore
    */
    protected $gitignore;
    
    /**
     * @var bool
     */
    protected $firstRun;

    /**
     * @var bool
     */
    protected $force;

    /**
     * @var PluginManager
     */
    protected $pluginManager;

    /**
     * @var ThemeManager
     */
    protected $themeManager;

    /**
     * @var Artisan
     */
    protected $artisan;

    /**
     * @var Composer
     */
    protected $composer;

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
        $this->themeManager = new ThemeManager();
        $this->artisan = new Artisan();
        $this->composer = new Composer();

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
        $this->themeManager->setPhp($php);
    }

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
            ->setDescription('Install October CMS.')
            ->addOption(
                'force',
                null,
                InputOption::VALUE_NONE,
                'Make the installer behave as if it is run for the first time. Existing files may get overwritten.'
            )
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
        $this->prepareEnv($input, $output);

        $this->makeConfig();

        if (!empty($php = $input->getOption('php'))) {
            $this->setPhp($php);
        }

        $this->gitignore = new Gitignore($this->getGitignore());

        $this->output->writeln('<info>Downloading latest October CMS...</info>');
        try {
            (new OctoberCms())->download($this->force);
        } catch (\LogicException $e) {
            $this->output->writeln('<comment>' . $e->getMessage() . '</comment>');
        }

        $this->output->writeln('<info>Installing composer dependencies...</info>');
        $this->composer->install();
        $this->composer->addDependency('offline/oc-bootstrapper');

        $this->output->writeln('<info>Setting up config files...</info>');
        $this->writeConfig($this->force);

        $this->prepareDatabase();

        $this->output->writeln('<info>Migrating database...</info>');
        $this->artisan->call('october:up');

        $this->output->writeln('<info>Installing Theme...</info>');
        $themeConfig = $this->config->cms['theme'];
        $this->themeManager->install($themeConfig);

        $this->output->writeln('<info>Installing Plugins...</info>');
        $pluginsConfigs = $this->config->plugins;

        foreach ($pluginsConfigs as $pluginConfig) {
            $this->pluginManager->install($pluginConfig);
        }

        $this->output->writeln('<info>Migrating plugin tables...</info>');
        $this->artisan->call('october:up');

        $this->output->writeln('<info>Setting up deployments...</info>');
        try {
            (new DeploymentInstaller($this->config, $this->gitignore, $this->output, $this->php))->install($this->force);
        } catch (\RuntimeException $e) {
            $this->output->writeln("<error>${e}</error>");
        }

        $this->output->writeln('<info>Creating .gitignore...</info>');
        $this->gitignore->write();

        if ($this->firstRun) {
            $this->output->writeln('<info>Removing demo data...</info>');
            $this->artisan->call('october:fresh');

            $this->output->writeln('<info>Creating README...</info>');
            $this->copyReadme();

            $this->output->writeln('<info>Cleaning up...</info>');
            $this->cleanup();
        }

        $this->output->writeln('<info>Clearing cache...</info>');
        $this->artisan->call('clear-compiled');
        $this->artisan->call('cache:clear');

        $this->output->writeln('<comment>Application ready! Build something amazing.</comment>');

        return true;
    }

    /**
     * Create the .env and config files.
     *
     * @param bool $force
     */
    protected function writeConfig($force = false)
    {
        $setup = new Setup($this->config, $this->output, $this->php);
        $setup->config();

        if ($this->firstRun) {
            $setup->env(false, true);

            return;
        }

        if ($this->fileExists('.env') && $force === false) {
            return $this->output->writeln('<comment>-> Configuration already set up. Use --force to regenerate.</comment>');
        }

        $setup->env();
    }

    /**
     * Get the .gitignore or create it using template.
     *
     * @return string
     */
    protected function getGitignore()
    {
        $target = $this->path('.gitignore');

        if ($this->fileExists($target)) {
            return $target;
        }

        $file     = $this->config->git['bareRepo'] ? 'gitignore.bare' : 'gitignore';
        $template = $this->getTemplate($file);

        $this->copy($template, $target);

        return $target;
    }
    
    /**
     * Copy the README template.
     *
     * @return void
     */
    protected function copyReadme()
    {
        $template = $this->getTemplate('README.md');
        $this->copy($template, 'README.md');
    }

    protected function cleanup()
    {
        if (! $this->firstRun) {
            return;
        }

        $remove = ['CONTRIBUTING.md', 'CHANGELOG.md', 'ISSUE_TEMPLATE.md'];
        foreach ($remove as $file) {
            $this->unlink(($this->path($file)));
        }
    }

    /**
     * Prepare database before migrations.
     */
    public function prepareDatabase()
    {
        // If SQLite database does not exist, create it
        if ($this->config->database['connection'] === 'sqlite') {
            $path = $this->config->database['database'];
            if (! $this->fileExists($path) && is_dir(dirname($path))) {
                $this->output->writeln("<info>Creating $path ...</info>");
                $this->touchFile($path);
            }
        }
    }

    /**
     * Prepare the environment
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    protected function prepareEnv(InputInterface $input, OutputInterface $output)
    {
        if (! class_exists('ZipArchive')) {
            throw new RuntimeException('The Zip PHP extension is not installed. Please install it and try again.');
        }

        $this->setOutput($output);

        $this->pluginManager->setOutput($output);
        $this->themeManager->setOutput($output);

        $this->force = $input->getOption('force');

        $this->firstRun = ! is_dir($this->path('bootstrap')) || $this->force;
    }
}
