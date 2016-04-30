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
     * @return $this
     */
    public function devEnvironment()
    {
        $this->writer
            ->setAppEnv('dev')
            ->copyConfigFileToEnv(['cms', 'app', 'database', 'mail'])
            ->setAppKey((new KeyGenerator())->generate());

        $this->setupDatabase();
        $this->setupTheme();
        $this->setupApp('dev');
        $this->setupMail('dev');

        return $this;
    }

    /**
     * @return $this
     */
    public function prodEnvironment()
    {
        $this->writer->env = 'prod';

        $this->setupCms();
        $this->setupTheme();
        $this->setupApp('prod');

        return $this;
    }

    /**
     * @param string $env
     */
    private function setupMail($env = 'prod')
    {
        $values = [
            'from.address' => $this->config->mail['address'],
            'from.name'    => $this->config->mail['name'],
        ];
        if ($env === 'dev') {
            $values['driver'] = $this->config->mail['driver'];
        }
        $this->writer->write('mail', $values);
    }

    /**
     *
     */
    private function setupDatabase()
    {
        // DB CONFIG
        $dbConnection = $this->config->database['connection'];
        $values       = ['default' => $dbConnection];

        foreach ($this->config->database as $key => $setting) {
            if ($key === 'connection') {
                continue;
            }
            $values["connections.{$dbConnection}.{$key}"] = $setting;
        }

        $this->writer->write('database', $values);
    }

    /**
     * @param string $env
     */
    private function setupApp($env = 'prod')
    {
        $values = [
            'url'    => $this->config->app['url'],
            'locale' => $this->config->app['locale'],
        ];

        if ($env === 'prod') {
            $values = [
                'debug'  => 'false',
                'locale' => $this->config->app['locale'],
            ];
        }

        $this->writer->write('app', $values);
    }

    /**
     *
     */
    private function setupCms()
    {
        $values = [
            'enableRoutesCache'    => true,
            'enableAssetCache'     => true,
            'enableCsrfProtection' => true,
        ];
        $this->writer->write('cms', $values);
    }

    /**
     *
     */
    private function setupTheme()
    {
        $activeTheme = explode(' ', $this->config->theme);
        $values      = [
            'activeTheme' => $activeTheme[0],
        ];
        $this->writer->write('cms', $values);
    }

}