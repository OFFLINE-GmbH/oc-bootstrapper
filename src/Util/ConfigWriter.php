<?php

namespace OFFLINE\Bootstrapper\October\Util;

use October\Rain\Config\Rewrite;

class ConfigWriter
{
    protected $writer;
    protected $dir;
    protected $env = 'dev';

    public function __construct(Rewrite $writer = null)
    {
        if ($writer === null) {
            $writer = new Rewrite();
        }
        $this->writer = $writer;
        $this->dir    = getcwd() . '/config';
    }

    public function setEnvironment($appEnv = 'dev')
    {
        $this->env = $appEnv;
        file_put_contents(getcwd() . '/.env', 'APP_ENV=' . $appEnv);

        return $this;
    }

    public function copyConfigFileToEnv($file)
    {
        $target = $this->dir . '/' . $this->env . '/' . $file . '.php';
        if ( ! file_exists($target)) {
            copy($this->dir . '/' . $file . '.php', $target);
        }

        return $target;
    }

    public function write($file, array $values)
    {
        $file = $this->copyConfigFileToEnv($file);

        $this->writer->toFile($file, $values);

        return $this;
    }

    public function setAppKey($key)
    {
        $this->writer->toFile($this->dir . '/app.php', compact('key'), false);
    }
}