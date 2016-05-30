<?php

namespace OFFLINE\Bootstrapper\October\Config;


use OFFLINE\Bootstrapper\October\Util\KeyGenerator;

/**
 * Class Setup
 * @package OFFLINE\Bootstrapper\October\Config
 */
class Setup
{
    /**
     * @var Config
     */
    protected $config;
    /**
     * @var Writer
     */
    protected $writer;

    /**
     * Setup constructor.
     *
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
        $this->writer = new Writer();
    }

    /**
     * Put env() calls into config files.
     *
     * @return void
     */
    public function config()
    {
        $this->database();
        $this->theme();
        $this->app();
        $this->mail();
        $this->cms();
    }

    /**
     * Write .env files.
     *
     * @return $this
     */
    public function env()
    {
        $this->writer->backupExistingEnv();

        $lines = [
            'APP_ENV'          => 'dev',
            'APP_URL'          => $this->config->app['url'],
            'APP_KEY'          => (new KeyGenerator())->generate(),
            'APP_DEBUG'        => (bool)$this->config->app['debug'] ? 'true' : 'false',
            '',
            'CMS_EDGE_UPDATES' => (bool)$this->config->cms['edgeUpdates'] ? 'true' : 'false',
            'CMS_ASSETS_CACHE' => 'false',
            'CMS_ROUTES_CACHE' => 'false',
            '',
            'DB_CONNECTION'    => $this->config->database['connection'],
            'DB_USERNAME'      => $this->config->database['username'],
            'DB_PASSWORD'      => $this->config->database['password'],
            'DB_DATABASE'      => $this->config->database['database'],
            'DB_HOST'          => $this->config->database['host'],
            '',
            'MAIL_DRIVER'      => $this->config->mail['driver'],
            'MAIL_NAME'        => '"' . $this->config->mail['name'] . '"',
            'MAIL_ADDRESS'     => $this->config->mail['address'],
        ];

        foreach ($lines as $key => $value) {
            $this->writer->writeEnvFile($key, $value);
        }

        $this->writer->createEnvExample();
        $this->writer->createEnvProduction();

        return $this;
    }

    /**
     * Write the mail configuration.
     *
     * @return void
     */
    protected function mail()
    {
        $values = [
            'driver' => "env('MAIL_DRIVER', 'log')",
        ];
        $this->writer->write('mail', $values);

        // Replace the inline 'address/name' config entry separately
        // since this edge case is not supported by the generic
        // Writer->write method.
        $contents = file_get_contents($this->writer->filePath('mail'));

        $regex   = "/'address'\s+=>\s+'[^']+',\s+\'name\'\s+=>\s+'[^']+'/";
        $replace = "'address' => env('MAIL_ADDRESS'), 'name' => env('MAIL_NAME')";

        file_put_contents($this->writer->filePath('mail'), preg_replace($regex, $replace, $contents));
    }

    /**
     * Write the database configuration.
     *
     * @return void
     */
    protected function database()
    {
        $values = ['default' => "env('DB_CONNECTION')"];

        foreach ($this->config->database as $key => $setting) {
            // Do nothing for the "connection" config entry since
            // this only specifies which "default" to use. This
            // entry is set separately above.
            if ($key === 'connection') {
                continue;
            }

            $values[$key] = "env('DB_" . strtoupper($key) . "')";
        }

        $this->writer->write('database', $values);
    }

    /**
     * Write the app configuration.
     *
     * @return void
     */
    protected function app()
    {
        $values = [
            'url'    => "env('APP_URL')",
            'key'    => "env('APP_KEY')",
            'debug'  => "env('APP_DEBUG', false)",
            'locale' => $this->config->app['locale'],
        ];

        $this->writer->write('app', $values);
    }

    /**
     * Write the cms configuration.
     *
     * @return void
     */
    protected function cms()
    {
        $values = [
            'edgeUpdates'          => "env('CMS_EDGE_UPDATES', false)",
            'enableRoutesCache'    => "env('CMS_ROUTES_CACHE', true)",
            'enableAssetCache'     => "env('CMS_ASSETS_CACHE', true)",
            'enableCsrfProtection' => "env('CMS_CSRF_PROTECTION', true)",
        ];

        $this->writer->write('cms', $values);
    }

    /**
     * Set the default theme.
     *
     * @return boolean
     */
    protected function theme()
    {
        try {
            $activeTheme = explode(' ', $this->config->cms['theme']);
        } catch (\RuntimeException $e) {
            // No theme set
            return false;
        }

        $values = [
            'activeTheme' => $activeTheme[0],
        ];

        $this->writer->write('cms', $values);

        return true;
    }

}