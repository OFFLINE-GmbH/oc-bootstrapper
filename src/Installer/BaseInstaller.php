<?php

namespace OFFLINE\Bootstrapper\October\Installer;


use OFFLINE\Bootstrapper\October\Config\Config;
use OFFLINE\Bootstrapper\October\Util\Gitignore;
use RuntimeException;
use Symfony\Component\Console\Output\OutputInterface;

abstract class BaseInstaller
{
    /**
     * Exit code for processes
     */
    const EXIT_CODE_OK = 0;
    /**
     * @var Gitignore
     */
    protected $gitignore;
    /**
     * @var OutputInterface
     */
    protected $output;
    /**
     * @var string
     */
    protected $php;

    public abstract function install();

    /**
     * @var Config
     */
    protected $config;

    /**
     * DeploymentInstaller constructor.
     *
     * @param Config          $config
     * @param Gitignore       $gitignore
     * @param OutputInterface $output
     */
    public function __construct(Config $config, Gitignore $gitignore, OutputInterface $output, $php)
    {
        $this->config    = $config;
        $this->gitignore = $gitignore;
        $this->output    = $output;
        $this->php       = $php;
    }

    /**
     * Creates a directory.
     *
     * @param $dir
     *
     * @return mixed
     * @throws \RuntimeException
     */
    protected function mkdir($dir)
    {
        if ( ! @mkdir($dir) && ! is_dir($dir)) {
            throw new RuntimeException('Could not create directory: ' . $dir);
        }

        return $dir;
    }

    /**
     * Checks if a directory is empty.
     *
     * @param $themeDir
     *
     * @return bool
     */
    protected function isEmpty($themeDir)
    {
        return count(glob($themeDir . '/*')) === 0;
    }

    /**
     * Removes .git Directories.
     *
     * @param $path
     */
    protected function cleanup($path)
    {
        $this->rmdir($path . DS . '.git');
    }

    /**
     * Removes a directory recursive.
     *
     * @param $dir
     *
     * @return mixed
     */
    public function rmdir($dir)
    {
        $entries = array_diff(scandir($dir), ['.', '..']);
        foreach ($entries as $entry) {
            $path = $dir . DS . $entry;
            is_dir($path) ? $this->rmdir($path) : unlink($path);
        }

        return rmdir($dir);
    }

    protected function write($line)
    {
        $this->output->writeln($line);
    }
}