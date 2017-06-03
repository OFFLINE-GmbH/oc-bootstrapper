<?php

namespace OFFLINE\Bootstrapper\October\Util;


class Gitignore
{
    /**
     * @var array
     */
    private $contents;
    private $file;

    public function __construct($file)
    {
        $this->file     = $file;
        $this->contents = file($file);
    }

    public function write()
    {
        file_put_contents($this->file, $this->contents);
    }

    public function add($line)
    {
        if ($this->hasLine($line)) {
            return;
        }

        $this->contents[] = $line . PHP_EOL;
    }

    public function hasLine($line)
    {
        foreach ($this->contents as $entry) {
            $entry = str_replace("\n", '', $entry);
            if (strtolower($line) === strtolower($entry)) {
                return true;
            }
        }

        return false;
    }

    protected function newLine()
    {
        $this->contents[] = PHP_EOL . PHP_EOL;
    }

    public function addPlugin($vendor, $plugin)
    {
        $header = $this->buildHeader($vendor, $plugin);
        if ($this->hasLine($header)) {
            return;
        }

        $this->newLine();
        $this->add($header);
        $this->add('!plugins/' . $vendor);
        $this->add('!plugins/' . $vendor . '/' . $plugin);
        $this->add('!plugins/' . $vendor . '/' . $plugin . '/**/*');
    }

    public function hasPluginHeader($vendor, $plugin)
    {
        $header = $this->buildHeader($vendor, $plugin);

        return $this->hasLine($header);
    }

    protected function buildHeader($vendor, $plugin)
    {
        return sprintf("# %s.%s", $vendor, $plugin);
    }


}