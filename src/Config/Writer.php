<?php

namespace OFFLINE\Bootstrapper\October\Config;

/**
 * Class Writer
 * @package OFFLINE\Bootstrapper\October\Util
 */
class Writer
{
    /**
     * Config directory.
     *
     * @var string
     */
    protected $dir;
    /**
     * Path to the env files.
     *
     * @var string
     */
    protected $env;

    /**
     * Writer constructor.
     */
    public function __construct()
    {
        $this->dir = getcwd() . DS . 'config';
        $this->env = getcwd() . DS . '.env';
    }

    /**
     * Write to .env file
     *
     * @param $key
     * @param $value
     *
     * @return $this
     */
    public function writeEnvFile($key, $value)
    {
        // If the key is numeric, a line break gets inserted
        if (is_numeric($key)) {
            $line = '';
        } else {
            $line = $key . '=' . $value;
        }

        file_put_contents($this->env, $line . PHP_EOL, FILE_APPEND);

        return $this;
    }

    /**
     * Backup an clear existing .env file.
     *
     * @return string|boolean
     */
    public function backupExistingEnv()
    {
        if (file_exists($this->env)) {
            $newEnv = $this->env . '.' . uniqid('original_', false);

            copy($this->env, $newEnv);
            $this->removeCurrentEnv();

            return $newEnv;
        }

        return false;
    }

    /**
     * Remove existing .env file.
     *
     * @return void
     */
    public function removeCurrentEnv()
    {
        if (file_exists($this->env)) {
            unlink($this->env);
        }
    }

    /**
     * Create .env.example
     *
     * @return $this
     */
    public function createEnvExample()
    {
        copy($this->env, $this->env . '.example');

        return $this;
    }

    /**
     * Create .env.example
     *
     * @return $this
     */
    public function createEnvProduction()
    {
        $file = $this->env . '.production';

        copy($this->env, $file);

        $this->replaceLine('APP_ENV', 'APP_ENV=production', $file);
        $this->replaceLine('APP_DEBUG', 'APP_DEBUG=false', $file);
        $this->replaceLine('APP_URL', 'APP_URL=https://', $file);

        $this->replaceLine('DB_USERNAME', 'DB_USERNAME=', $file);
        $this->replaceLine('DB_PASSWORD', 'DB_PASSWORD=', $file);
        $this->replaceLine('DB_DATABASE', 'DB_DATABASE=', $file);
        $this->replaceLine('DB_HOST', 'DB_HOST=localhost', $file);

        $this->replaceLine('MAIL_DRIVER', 'MAIL_DRIVER=mail', $file);

        $this->replaceLine('ASSETS_CACHE', 'ASSETS_CACHE=true', $file);
        $this->replaceLine('ROUTES_CACHE', 'ROUTES_CACHE=true', $file);
        $this->replaceLine('ENABLE_CSRF', 'ENABLE_CSRF=true', $file);

        return $this;
    }

    /**
     * Replace a line in a file.
     *
     * @param $startsWith
     * @param $replace
     * @param $file
     */
    public function replaceLine($startsWith, $replace, $file)
    {
        $lines = file($file);

        $contents = [];
        foreach ($lines as $num => $line) {
            if (strpos($line, $startsWith) === 0) {
                $contents[] = $replace . PHP_EOL;
            } else {
                $contents[] = $line;
            }
        }

        file_put_contents($file, $contents);
    }

    /**
     * Restores a backed up .env file.
     *
     * @param $newEnv
     */
    public function restore($newEnv)
    {
        copy($newEnv, $this->env);
    }

    /**
     * Remove a line from a file.
     *
     * @param array $startsWith
     * @param       $file
     */
    protected function removeLines(array $startsWith, $file)
    {
        $lines = file($file);

        $contents = [];
        foreach ($lines as $line) {

            $keepLine = true;

            foreach ($startsWith as $search) {
                if (strpos($line, $search) === 0) {
                    $keepLine = false;
                    break;
                }
            }

            if ($keepLine) {
                $contents[] = $line;
            }
        }

        file_put_contents($file, $contents);
    }

    /**
     * Return the full config file path.
     *
     * @param      $subject
     *
     * @return string
     */
    public function filePath($subject)
    {
        return $this->dir . DS . $subject . '.php';
    }

    /**
     * Write multiple values to a file.
     *
     * @param       $file
     * @param array $values
     *
     * @return $this
     */
    public function write($file, array $values)
    {
        $file     = $this->filePath($file);
        $contents = file_get_contents($file);

        foreach ($values as $key => $value) {
            // No quotes for env() calls
            $replace = substr($value, 0, 4) === 'env(' ? $value : "'{$value}'";
            // Replace "key => value" entries in the file's contents
            $contents = preg_replace("/('{$key}'\s+=>\s+)([^\n\]]+),/", "$1" . $replace . ',', $contents);
        }

        file_put_contents($file, $contents);

        return $this;
    }

    /**
     * Checks if an .env file is present.
     *
     * @return boolean
     */
    public function hasEnv()
    {
        return file_exists($this->env);
    }
}