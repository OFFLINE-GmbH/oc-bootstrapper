<?php

namespace OFFLINE\Bootstrapper\October\Console;

use InvalidArgumentException;
use LogicException;
use OFFLINE\Bootstrapper\October\Config\Setup;
use OFFLINE\Bootstrapper\October\Deployment\DeploymentFactory;
use OFFLINE\Bootstrapper\October\Downloader\OctoberCms;
use OFFLINE\Bootstrapper\October\Exceptions\DeploymentExistsException;
use OFFLINE\Bootstrapper\October\Exceptions\ThemeExistsException;
use OFFLINE\Bootstrapper\October\Manager\PluginManager;
use OFFLINE\Bootstrapper\October\Manager\ThemeManager;
use OFFLINE\Bootstrapper\October\Util\Artisan;
use OFFLINE\Bootstrapper\October\Util\CliIO;
use OFFLINE\Bootstrapper\October\Util\Composer;
use OFFLINE\Bootstrapper\October\Util\ConfigMaker;
use OFFLINE\Bootstrapper\October\Util\Gitignore;
use OFFLINE\Bootstrapper\October\Util\UsesTemplate;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

/**
 * Class InstallCommand
 * @package OFFLINE\Bootstrapper\October\Console
 */
class InstallCommand extends Command
{
    use ConfigMaker, UsesTemplate, CliIO;

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
     * Set output for all components
     */
    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;
        $this->pluginManager->setOutput($output);
        $this->themeManager->setOutput($output);
        $this->composer->setOutput($output);
    }

    public function setWithGitDirectory(bool $withGitDirectory)
    {
        $this->pluginManager->setWithGitDirectory($withGitDirectory);
        $this->themeManager->setWithGitDirectory($withGitDirectory);
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
            )
            ->addOption(
                'templates-from',
                null,
                InputOption::VALUE_OPTIONAL,
                'Specify from where to fetch template files (git remote)',
                ''
            );
    }

    /**
     * Execute the command.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return mixed
     * @throws RuntimeException
     * @throws InvalidArgumentException
     * @throws LogicException
     * @throws RuntimeException
     * @throws InvalidArgumentException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ( ! class_exists('ZipArchive')) {
            throw new RuntimeException('The Zip PHP extension is not installed. Please install it and try again.');
        }

        $this->setOutput($output);

        $this->force = $input->getOption('force');

        $this->firstRun = ! $this->dirExists($this->path('bootstrap')) || $this->force;

        if ($input->getOption('templates-from')) {
            $remote = $input->getOption('templates-from');
            $this->fetchTemplateFiles($remote);
        }

        $this->makeConfig();

        if ( ! empty($php = $input->getOption('php'))) {
            $this->setPhp($php);
        }

        $this->gitignore = new Gitignore($this->getGitignore());

        $this->write('Downloading latest October CMS...');
        try {
            (new OctoberCms())->download($this->force);
        } catch (\LogicException $e) {
            $this->write($e->getMessage(), 'comment');
        } catch (Throwable $e) {
            $this->write($e->getMessage(), 'error');

            return false;
        }

        $this->write('Patching composer.json...');
        $this->patchComposerJson();

        $this->write('Installing composer dependencies...');
        $this->composer->install();

        $this->write('Setting up config files...');
        $this->writeConfig($this->force);

        $this->prepareDatabase();

        $this->write('Migrating database...');
        $this->artisan->call('october:up');

        if (isset($this->config->git['keepRepo'])) {
            $this->setWithGitDirectory($this->config->git['keepRepo']);
        }

        $themeDeclaration = false;
        try {
            $themeDeclaration = $this->config->cms['theme'];
        } catch (RuntimeException $e) {
            $this->write('No theme to install', 'comment');
        }

        if ($themeDeclaration) {
            $this->write('Installing Theme...');
            try {
                $this->themeManager->install($themeDeclaration);
            } catch (ThemeExistsException $e) {
                $this->write($e->getMessage(), 'comment');
            } catch (Throwable $e) {
                $this->write('Failed to install theme: ' . $e->getMessage(), 'error');

                return false;
            }
        }

        if (array_key_exists('project', $this->config->cms)) {
            $this->write('Setting Project ID...');
            $this->artisan->call('october:util set project --projectId=' . $this->config->cms['project']);
        }

        $pluginsDeclarations = [];
        try {
            $pluginsDeclarations = $this->config->plugins;
        } catch (RuntimeException $e) {
            $this->write('No plugins to install');
        }

        if ($pluginsDeclarations) {
            $this->write('Installing Plugins...');
            $this->installPlugins($pluginsDeclarations);
        }

        $deployment = false;
        try {
            $deployment = $this->config->git['deployment'];
        } catch (RuntimeException $e) {
            $this->write('No deployments to install');
        }

        if ($deployment) {
            $this->write("Setting up ${deployment} deployment.");
            try {
                $deploymentObj = DeploymentFactory::createDeployment($deployment, $this->config);
                $deploymentObj->install($this->force);
            } catch (DeploymentExistsException $e) {
                $this->write($e->getMessage(), 'comment');
            } catch (Throwable $e) {
                $this->write($e->getMessage(), 'error');

                return false;
            }
        }

        $this->write('Creating .gitignore...');
        $this->gitignore->write();

        if ($this->firstRun) {
            $this->write('Removing demo data...');
            $this->artisan->call('october:fresh');

            // Make sure the demo data is gone! (october:fresh is unreliable)
            $this->rrmdir('themes/demo');
            $this->rrmdir('plugins/demo');

            $this->write('Creating README...');
            $this->copyReadme();

            $this->write('Cleaning up...');
            $this->cleanup();
        }

        $this->write('Clearing cache...');
        $this->artisan->call('clear-compiled');
        $this->artisan->call('cache:clear');

        $this->write('Application ready! Build something amazing.', 'comment');

        return true;
    }

    /**
     * Handle installing plugins and updating them if possible
     *
     * @param array $pluginsDeclarations
     *
     * @return void
     */
    public function installPlugins($pluginsDeclarations)
    {
        foreach ($pluginsDeclarations as $pluginDeclaration) {
            $pluginInstalled = $this->pluginManager->isInstalled($pluginDeclaration);
            $installPlugin = ! $pluginInstalled;

            list($update, $vendor, $plugin, $remote, $branch) = $this->pluginManager->parseDeclaration($pluginDeclaration);

            if ($pluginInstalled && ($update || ! $this->gitignore->hasPluginHeader($vendor, $plugin))) {
                if ($pluginInstalled && $remote) {
                    $this->write("Removing ${vendor}.${plugin} directory to re-download the newest version...",
                        'comment');

                    $this->pluginManager->removeDir($pluginDeclaration);
                    $installPlugin = true;
                } else {
                    $this->write("-> Skipping re-download of ${vendor}.${plugin}", 'comment');
                    $installPlugin = false;
                }
            }

            if ($installPlugin) {
                try {
                    $this->pluginManager->install($pluginDeclaration);
                } catch (Throwable $e) {
                    $this->write($e->getMessage(), 'error');
                    continue;
                }
            }

            if ($update === false && $remote) {
                $this->gitignore->addPlugin($vendor, $plugin);
            }
        }

        $this->write('Migrating plugin tables...');
        $this->artisan->call('october:up');
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
            return $this->write('-> Configuration already set up. Use --force to regenerate.', 'comment');
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

        $templateName = 'gitignore';

        if ($this->config->git['bareRepo']) {
            $templateName .= '.bare';
        }

        $template = $this->getTemplate($templateName);

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
        if ( ! $this->firstRun) {
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
            if ( ! $this->fileExists($path) && is_dir(dirname($path))) {
                $this->write("Creating $path ...");
                touch($path);
            }
        }
    }

    /**
     * Add oc-bootstrapper as a local dependency.
     *
     * @return void
     */
    protected function patchComposerJson()
    {
        if ( ! $this->fileExists('composer.json')) {
            $this->write('Failed to locate composer.json in local directory', 'error');

            return;
        }

        $structure = json_decode(file_get_contents($this->path('composer.json')));
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->write('Failed to parse composer.json', 'error');

            return;
        }

        $structure->require->{'offline/oc-bootstrapper'} = '^' . VERSION;

        $contents = json_encode($structure, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if (file_put_contents($this->path('composer.json'), $contents) === false) {
            $this->write('Failed to write new composer.json', 'error');
        }
    }

    private function rrmdir(string $dir)
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object !== "." && $object !== "..") {
                    if (filetype($dir . "/" . $object) === "dir") {
                        $this->rrmdir($dir . "/" . $object);
                    } else {
                        unlink($dir . "/" . $object);
                    }
                }
            }
            reset($objects);
            rmdir($dir);
        }
    }
}
