<?php

namespace OFFLINE\Bootstrapper\October\Console;

use OFFLINE\Bootstrapper\October\Manager\PluginManager;
use OFFLINE\Bootstrapper\October\Manager\ThemeManager;
use OFFLINE\Bootstrapper\October\Util\Artisan;
use OFFLINE\Bootstrapper\October\Util\RunsProcess;
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
    use RunsProcess;

    /**
     * @var Artisan
     */
    protected $artisan;

    /**
     * @var PluginManager
     */
    protected $pluginManager;

    /**
     * @var ThemeManager
     */
    protected $themeManager;

    /**
     * @var OutputInterface
     */
    protected $output;

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
     * @throws InvalidArgumentException
     * @return void
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
        $this->output = $output;

        try {
            $this->makeConfig();
        } catch (RuntimeException $e) {
            return $this->output->writeln($e->message);
        }

        // 1. Run `october install` command, which will install all plugins and themes that are not installed yet

        if (!empty($php = $input->getOption('php'))) {
            $this->setPhp($php);
        }

        $this->runProcess($this->php . ' october install', 'Installation failed!');

        // 2. Remove every plugin / theme that has git repo specified in october.yaml, for `october:update` command not to try update them

        $pluginsConfigs = $this->config->plugins;

        foreach ($pluginsConfigs as $pluginConfig) {
            list($vendor, $plugin, $remote, $branch) = $this->pluginManager->parsePluginDeclaration($pluginConfig);

            if (!empty($remote)) {
                $this->pluginManager->removePluginDir($pluginConfig);
            }
        }

        try {
            $themeConfig = $this->config->cms['theme'];
        } catch (\RuntimeException $e) {
            // No theme set
            $themeConfig = false;
        }
        if ($themeConfig) {
            $themeConfig = $this->config->cms['theme'];
            list($theme, $remote) = $this->themeManager->parseThemeDeclaration($themeConfig);
            if ($remote) {
                $this->themeManager->removeThemeDir($themeConfig);
            }
        }

        // 3. Run `php artisan october:update`, which updates core and marketplace plugins / themes

        $this->artisan->call('october:update');

        // 4. Git clone all plugins and themes again

        // 5. Run `php artisan october:up` to migrate all versions of plugins

        $this->artisan->call('october:up');

        // 6. Run `composer update` to update all composer packages

        // 7. Optionally commit and push to git repo


        return true;
    }
}
