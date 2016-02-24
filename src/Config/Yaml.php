<?php

namespace OFFLINE\Bootstrapper\October\Config;


use Symfony\Component\Yaml\Parser;

class Yaml
{
    protected $config;

    public function __construct($file, Parser $parser = null)
    {
        if ($parser === null) {
            $parser = new Parser();
        }

        try {
            $this->config = $parser->parse(file_get_contents($file));
        } catch (ParseException $e) {
            throw new \RuntimeException('Unable to parse the YAML string: %s', $e->getMessage());
        }
    }

    public function __get($name) {
        return $this->config[$name];
    }
}