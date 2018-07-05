<?php

namespace OFFLINE\Bootstrapper\October\Installer;


use OFFLINE\Bootstrapper\October\Config\Config;
use OFFLINE\Bootstrapper\October\Util\Gitignore;
use RuntimeException;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

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
     * Removes a directory recursively.
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
            is_dir($path) ? $this->rmdir($path) : $this->unlink($path);
        }

        return rmdir($dir);
    }

    /**
     * Delete a file. Fallback to OS native rm command if the file to be deleted is write protected.
     *
     * @param string $file
     */
    public function unlink($file)
    {
        if (is_writable($file)) {
            unlink($file);
        } else {
            // Just to be sure that we don't delete "too much" by accident...
            if (\in_array($file, ['*', '**', '.', '..', '/', '/*'])) {
                return;
            }

            // If there are write-protected files present (primarily on Windows) we can use
            // the force mode of rm to remove it. PHP itself won't delete write-protected files.
            $command = stripos(PHP_OS, 'WIN') === 0 ? 'rm /f' : 'rm -f';
            $file    = escapeshellarg($file);

            (new Process($command . ' ' . $file))->setTimeout(60)->run();
        }
    }

    protected function write($line)
    {
        $this->output->writeln($line);
    }
}