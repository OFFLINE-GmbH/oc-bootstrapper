<?php

namespace OFFLINE\Bootstrapper\October\Config;


use OFFLINE\Bootstrapper\October\Util\ConfigWriter;
use OFFLINE\Bootstrapper\October\Util\KeyGenerator;

class Setup
{
    protected $config;
    protected $writer;

    public function __construct(ConfigInterface $config)
    {
        $this->config = $config;
        $this->writer = new ConfigWriter();
    }

    public function devEnvironment()
    {
        $this->writer
            ->setAppEnv('dev')
            ->copyConfigFileToEnv(['cms', 'app', 'database'])
            ->setAppKey((new KeyGenerator())->generate());

        $this->setupDatabase();
        $this->setupAppEnv();

        return $this;
    }

    public function prodEnvironment()
    {
        $this->writer->env = 'prod';

        $this->setupCms();

        $this->setupAppProd();

        return $this;
    }

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

    private function setupAppProd()
    {
        $values = [
            'debug'  => 'false',
            'locale' => $this->config->app['locale'],
        ];
        $this->writer->write('app', $values);
    }

    private function setupAppEnv()
    {

        $values = [
            'url'    => $this->config->app['url'],
            'locale' => $this->config->app['locale'],
        ];
        $this->writer->write('app', $values);
    }

    private function setupCms()
    {
        $values = [
            'enableRoutesCache'    => true,
            'enableAssetCache'     => true,
            'enableCsrfProtection' => true,
        ];
        $this->writer->write('cms', $values);
    }

}