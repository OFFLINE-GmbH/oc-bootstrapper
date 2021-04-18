<?php


namespace OFFLINE\Bootstrapper\October\Installer;


use OFFLINE\Bootstrapper\October\Config\Yaml;
use OFFLINE\Bootstrapper\October\Deployment\DeploymentFactory;
use OFFLINE\Bootstrapper\October\Exceptions\DeploymentExistsException;
use OFFLINE\Bootstrapper\October\Util\CliIO;
use OFFLINE\Bootstrapper\October\Util\Composer;
use OFFLINE\Bootstrapper\October\Util\Gitignore;
use OFFLINE\Bootstrapper\October\Util\RunsProcess;
use OFFLINE\Bootstrapper\October\Util\UsesTemplate;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\LogicException;
use Symfony\Component\Process\Exception\RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;

class OctoberInstaller
{
    use CliIO;
    use UsesTemplate;
    use RunsProcess;

    /**
     * @var Yaml
     */
    public $config;
    /**
     * @var bool
     */
    public $force;
    /**
     * @var Composer
     */
    public $composer;
    /**
     * @var string
     */
    public $php = 'php';
    /**
     * @var string
     */
    public $cwd = '';
    /**
     * @var string
     */
    public $env = '.env';
    /**
     * @var Gitignore
     */
    public $gitignore;

    public function __construct(
        OutputInterface $output,
        Yaml $config,
        Composer $composer,
        string $php,
        bool $force
    ) {
        $this->output = $output;
        $this->config = $config;
        $this->force = $force;
        $this->composer = $composer;
        $this->php = $php;
        $this->cwd = getcwd();
        $this->env = $this->path('.env');

        $this->composer->setOutput($this->output);
    }

    public function run()
    {
        $key = 'HFSVD-O7RQD-6TCHL-O0SRO';

        $this->write('Creating new October project...');

        // Create project using composer
        if ($this->force || !is_dir($this->path('bootstrap'))) {
            $tmpDir = 'install-tmp';
            $this->ensureBlankCwd();
            $this->composer->createProject($tmpDir);
            $this->moveUp($tmpDir);
        }

        // Activate the license key
        if (!file_exists($this->path('auth.json'))) {
            $this->write('Registering license key...');
            $this->registerLicense($key);
        }

        // Patch configuration and .env files, run migraitons.
        $this->write('Setting up configuration files...');
        $this->setupConfig();
        $this->migrate();

        // Copy the .gitignore file over from the templates folder.
        $this->setupGitignore();
        $this->gitignore = new Gitignore($this->path('.gitignore'));

        // Install the theme.
        $this->write('Installing theme...');
        $this->setupTheme();

        // Install all plugins.
        $this->write('Installing plugins...');
        $this->setupPlugins();

        // If a deployment is configured, set it up.
        if (isset($this->config->git['deployment']) && $deployment = $this->config->git['deployment']) {
            $this->write("Setting up ${deployment} deployment.");
            $this->setupDeployment($deployment);
        }

        // If this is the first run (=force option enabled), remove any demo data.
        if ($this->force) {
            $this->write('Removing demo data...');
            $this->runProcess($this->php . ' artisan october:fresh', 'Failed to remove demo data');

            $this->write('Creating README...');
            $this->copyTemplateToCwd('README.md');

            $this->write('Cleaning up...');
            $this->cleanup();
        }

        // Make sure the cache is cleared in case we configured an already existing installation.
        $this->write('Clearing cache...');
        $this->runProcess($this->php . ' artisan clear-compiled', 'Failed to clear compiled files');
        $this->runProcess($this->php . ' artisan cache:clear', 'Failed to clear cache');

        $this->write('Application ready! Build something amazing.', 'comment');
    }

    /**
     * Uses the license.php registration file to register a license key.
     *
     * @param string $key
     *
     * @return bool
     */
    protected function registerLicense(string $key)
    {
        $copied = $this->copyTemplateToCwd('license.php');
        if (!$copied) {
            $this->exitError('Failed to copy license.php to project');
        }

        $command = sprintf('%s -f %s/license.php %s', $this->php, $this->cwd, $key);

        $activated = $this->runProcess($command, 'Failed to activate license');
        // Always unlink the helper script.
        $unlinked = unlink(sprintf('%s/license.php', $this->cwd));

        if (!$activated) {
            $this->exitError();
        }

        return $unlinked;
    }

    /**
     * Patches config files and inserts .env variables.
     *
     * @param        $line
     * @param string $surround
     */
    protected function setupConfig()
    {
        $this->patchCmsConfig();
        $this->patchEnv();

        $this->setEnvVar('APP_NAME', $this->config->app['name'] ?? 'October CMS');
        $this->setEnvVar('APP_ENV', $this->config->app['env'] ?? 'local');
        $this->setEnvVar('APP_DEBUG', (bool)$this->config->app['debug'] ? 'true' : 'false');
        $this->setEnvVar('APP_URL', $this->config->app['url'] ?? 'http://localhost');
        $this->setEnvVar('APP_LOCALE', $this->config->app['locale'] ?? 'en');

        $this->setEnvVar('BACKEND_URI', $this->config->app['backendUri'] ?? '/backend');
        $this->setEnvVar('ACTIVE_THEME', $this->parseThemeSource($this->config->cms['theme'])['name'] ?? 'demo');
        $this->setEnvVar('LINK_POLICY', $this->config->cms['linkPolicy'] ?? 'detect');
        $this->setEnvVar('LOG_CHANNEL', $this->config->app['logChannel'] ?? 'single');

        $this->setEnvVar('CMS_ROUTES_CACHE', $this->handleConfigBool($this->config->cms['routesCache'] ?? false));
        $this->setEnvVar('CMS_ASSET_CACHE', $this->handleConfigBool($this->config->cms['assetCache'] ?? false));
        $this->setEnvVar('CMS_ASSET_MINIFY', $this->handleConfigBool($this->config->cms['enableAssetMinify'] ?? false));
        $this->setEnvVar('CMS_DB_TEMPLATES', $this->handleConfigBool($this->config->cms['dbTemplates'] ?? false));
        $this->setEnvVar('CMS_SAFE_MODE', $this->handleConfigBool($this->config->cms['enableSafeMode'] ?? false));

        $this->setEnvVar('DB_CONNECTION', $this->config->database['connection'] ?? 'mysql');
        $this->setEnvVar('DB_HOST', $this->config->database['host'] ?? 'localhost');
        $this->setEnvVar('DB_PORT', $this->config->database['port'] ?? '3306');
        $this->setEnvVar('DB_DATABASE', $this->config->database['database'] ?? 'database');
        $this->setEnvVar('DB_USERNAME', $this->config->database['username'] ?? 'root');
        $this->setEnvVar('DB_PASSWORD', $this->config->database['password'] ?? '');

        $this->setEnvVar('MAIL_MAILER', $this->config->mail['mailer'] ?? 'log');
        $this->setEnvVar('MAIL_HOST', $this->config->mail['host'] ?? 'null');
        $this->setEnvVar('MAIL_PORT', $this->config->mail['port'] ?? 'null');
        $this->setEnvVar('MAIL_USERNAME', $this->config->mail['username'] ?? 'null');
        $this->setEnvVar('MAIL_PASSWORD', $this->config->mail['password'] ?? 'null');
        $this->setEnvVar('MAIL_ENCRYPTION', $this->config->mail['encryption'] ?? 'null');
        $this->setEnvVar('MAIL_FROM_ADDRESS', $this->config->mail['from_address'] ?? 'noreply@example.com');
        $this->setEnvVar('MAIL_FROM_NAME', '"' . ($this->config->mail['from_name'] ?? '${APP_NAME}') . '"');

        copy($this->env, $this->env . '.example');
        copy($this->env, $this->env . '.production');

        $this->setEnvVar('APP_KEY', 'Change_me!!!', 'production');
        $this->setEnvVar('APP_ENV', 'production', 'production');
        $this->setEnvVar('APP_DEBUG', 'false', 'production');
        $this->setEnvVar('APP_URL', 'https://', 'production');
        $this->setEnvVar('DB_USERNAME', '', 'production');
        $this->setEnvVar('DB_PASSWORD', '', 'production');
        $this->setEnvVar('DB_DATABASE', '', 'production');
        $this->setEnvVar('DB_HOST', '', 'production');
        $this->setEnvVar('MAIL_MAILER', 'mail', 'production');
        $this->setEnvVar('CMS_ROUTES_CACHE', 'true', 'production');
        $this->setEnvVar('CMS_ASSET_CACHE', 'true', 'production');
        $this->setEnvVar('CMS_ASSET_MINIFY', 'null', 'production');
    }

    /**
     * Install the configured theme.
     */
    protected function setupTheme()
    {
        $theme = $this->parseThemeSource($this->config->cms['theme']);
        if (!isset($theme['vendor']) || !isset($theme['name'])) {
            $this->exitError('Please define your theme name in the "vendor/theme-name" format.');
        }

        $fullname = sprintf('%s/%s', $theme['vendor'], $theme['name']);

        if (is_dir($this->path('themes', $theme['name']))) {
            $this->output->writeLn(
                sprintf('<fg=cyan>    - Theme %s is already installed, skipping.', $fullname) . '</>'
            );

            return;
        }

        $command = sprintf('theme:install %s', escapeshellarg($fullname));
        if (isset($theme['source'])) {
            $command .= ' --from=' . escapeshellarg($theme['source']);
        }
        if (isset($theme['version'])) {
            $command .= ' --want=' . escapeshellarg($theme['version']);
        }
        $lock = $this->config->cms['lockTheme'] ?? false;
        if ($lock !== true) {
            $command .= ' --no-lock';
        }

        $installed = $this->runProcess($this->php . ' artisan ' . $command,
            sprintf('Failed to install theme %s', $fullname), 120);
        if (!$installed) {
            $this->exitError();
        }
    }

    /**
     * Install the configured theme.
     */
    protected function setupPlugins()
    {
        if (!is_array($this->config->plugins)) {
            $this->write('    -> No plugins to install.', 'comment');

            return;
        }

        foreach ($this->config->plugins as $pluginSource) {
            $plugin = $this->parsePluginSource($pluginSource);
            $fullname = sprintf('%s.%s', $plugin['vendor'], $plugin['name']);

            // Check if the plugin is already installed.
            $path = $this->path('plugins', strtolower($plugin['vendor']), strtolower($plugin['name']));
            if (is_dir($path)) {
                $this->output->writeLn('<fg=cyan>    - ' . $pluginSource . ' is already installed, skipping.</>');
                continue;
            }

            $this->write('    - ' . $pluginSource, 'comment');

            $command = sprintf('plugin:install %s', escapeshellarg($fullname));
            if (isset($plugin['source'])) {
                $command .= ' --from=' . escapeshellarg($plugin['source']);
            }
            if (isset($plugin['version'])) {
                $command .= ' --want=' . escapeshellarg($plugin['version']);
            }

            $installed = $this->runProcess(
                $this->php . ' artisan ' . $command,
                'Failed to install plugin ' . $pluginSource,
                120
            );
            if (!$installed) {
                $this->exitError();
            }
        }

        $this->migrate();
    }


    protected function setEnvVar($variable, $value, $suffix = '')
    {
        $file = $this->env . ($suffix ? '.' . $suffix : '');

        $variable = preg_quote($variable, '/');

        $new = preg_replace("/($variable)=([^\n]+)/i", '$1=' . $value, file_get_contents($file));

        file_put_contents($file, $new);
    }


    /**
     * Move all the contents of a folder one level up.
     *
     * @throws RuntimeException
     * @throws LogicException
     */
    protected function moveUp($dir)
    {
        $source = $this->cwd . DS . $dir;

        (new Process(sprintf('mv %s %s', $source . '/*', $this->cwd)))->run();
        (new Process(sprintf('mv %s %s', $source . '/.*', $this->cwd)))->run();
        (new Process(sprintf('rm -rf %s', $source)))->run();

        if (is_dir($source)) {
            $this->write(sprintf('Install directory %s cold not be removed. Delete it manually.', $dir), 'warning');
        }
    }

    /**
     * Exists the project with an error code and error message.
     *
     * @param $message
     */
    protected function exitError($message = '')
    {
        if ($message) {
            $this->output->write($message, 'error');
        }
        exit(2);
    }

    /**
     * Parses a source declaration like "theme-name (git@remote:repo.git#version)
     *
     * @param string $source
     *
     * @return array
     */
    protected function parseThemeSource(string $source): array
    {
        preg_match(
            "/(?<vendor>[^ \/]+)\/(?<name>[^ ]+) ?(?:\((?<source>[^\)\#]+)\#?(?<version>[^\)]+)?\))?/i",
            $source,
            $matches
        );

        return $matches;
    }

    /**
     * Parses a source declaration like "Vendor.Plguin (git@remote:repo.git#version)"
     *
     * @param string $source
     *
     * @return array
     */
    protected function parsePluginSource(string $source): array
    {
        preg_match("/(?<update>\^)?(?<vendor>[^\.]+)\.(?<name>[^ #]+)(?: ?\((?<source>[^\#)]+)(?:#(?<version>[^\)]+)?)?)?/",
            $source, $matches);

        return $matches;
    }

    /**
     * Safely parses a YAML config input to its string representation.
     *
     * @param $input
     *
     * @return string
     */
    protected function handleConfigBool($input)
    {
        if ($input === 'null') {
            return 'null';
        }
        if ($input === 'false') {
            return false;
        }

        return (bool)$input === true ? 'true' : 'false';
    }

    /**
     * Makes sure all required env variables are present.
     */
    protected function patchEnv()
    {
        $this->insertAfter($this->env, 'CMS_ASSET_CACHE', 'CMS_ASSET_MINIFY=null');
    }

    /**
     * Makes sure the required env() calls are in config/cms.php.
     */
    protected function patchCmsConfig()
    {
        $file = $this->path('config', 'cms.php');
        $contents = file_get_contents($file);

        $replacements = [
            'enable_asset_minify' => "env('CMS_ASSET_MINIFY', null)",
        ];

        foreach ($replacements as $key => $value) {
            $contents = preg_replace("/('{$key}'\s+=>\s+)([^\n\]]+),/", "$1" . $value . ',', $contents);
        }

        return file_put_contents($file, $contents);
    }

    /**
     * Inserts content after a given line in a file.
     */
    protected function insertAfter($file, $search, $insert)
    {
        $contents = file_get_contents($file);

        $key = explode('=', $insert)[0] ?? '';

        // Don't insert if already present.
        if (strpos($contents, $key) !== false) {
            return;
        }

        $search = preg_quote($search, '/');

        $contents = preg_replace("/({$search}[^\n]+\n)/i", "$1" . $insert . "\n", $contents);

        file_put_contents($file, $contents);
    }

    /**
     * Makes sure a SQLite file exists, if sqlite is the current db driver.
     */
    protected function migrate()
    {
        // If SQLite database does not exist, create it
        if ($this->config->database['connection'] === 'sqlite') {
            $path = $this->config->database['database'];
            if (!file_exists(realpath($path)) && is_dir(dirname($path))) {
                $this->write("Creating $path ...");
                touch($path);
            }
        }

        $this->write("Migrating database...");
        $migrated = $this->runProcess($this->php . ' artisan october:migrate', 'Failed to migrate database');
        if (!$migrated) {
            $this->exitError();
        }
    }

    /**
     * Setup the .gitignore file.
     */
    protected function setupGitignore()
    {
        if (!$this->force && file_exists($this->path('.gitignore'))) {
            return;
        }

        $this->write('Setting up .gitignore...');

        // Remove the default .gitignore.
        $file = $this->path('.gitignore');
        if (file_exists($file)) {
            unlink($file);
        }

        $templateName = 'gitignore';

        if ($this->config->git['bareRepo']) {
            $templateName .= '.bare';
        }

        $copied = $this->copyTemplateToCwd($templateName, '.gitignore');
        if (!$copied) {
            $this->exitError('Failed to create .gitignore');
        }
    }


    /**
     * Setup the deployment templates.
     */
    protected function setupDeployment($deployment)
    {
        try {
            $deploymentObj = DeploymentFactory::createDeployment($deployment, $this->config);
            $deploymentObj->install($this->force);
        } catch (DeploymentExistsException $e) {
            $this->output->writeLn('<fg=cyan>    - ' . $e->getMessage() . '</>');
        } catch (Throwable $e) {
            $this->exitError($e->getMessage());
        }
    }

    /**
     * Cleanup after installation.
     */
    protected function cleanup()
    {
        if (!$this->force) {
            return;
        }

        $remove = ['SECURITY.md', 'CHANGELOG.md'];
        foreach ($remove as $file) {
            $file = $this->cwd . DS . $file;
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }

    /**
     * Returns a path relative to the cwd.
     *
     * @param $path
     *
     * @return string
     */
    protected function path(...$path)
    {
        return $this->cwd . DS . implode(DS, $path);
    }

    /**
     * Makes sure there are no old October directories around.
     */
    protected function ensureBlankCwd()
    {
        @unlink($this->path('composer.json'));
        @unlink($this->path('composer.lock'));
        $this->rrmdir($this->path('bootstrap'));
        $this->rrmdir($this->path('config'));
        $this->rrmdir($this->path('modules'));
        $this->rrmdir($this->path('vendor'));
    }

    /**
     * Removes a directory recursively.
     *
     * @param string $dir
     */
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