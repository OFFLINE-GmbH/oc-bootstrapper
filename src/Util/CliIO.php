<?php

namespace OFFLINE\Bootstrapper\October\Util;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command line interface Input Output trait
 */
trait CliIO
{
    /**
     * Exit code for processes
     */
    public $exitCodeOk = 0;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var InputInterface
     */
    protected $input;

    /**
     * Set the value of output
     *
     * @param  OutputInterface  $output
     * @return  self
     */
    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;

        return $this;
    }

    /**
     * Set the value of input
     *
     * @param  InputInterface  $input
     * @return  self
     */
    public function setInput(InputInterface $input)
    {
        $this->input = $input;

        return $this;
    }

    /**
     * Writes new line to output
     *
     * @param string $line
     * @param string $surround html tag to surround the message
     * @return void
     */
    protected function write($line, $surround = "info")
    {
        $this->output->writeln("<${surround}>${line}</${surround}>");
    }
}
