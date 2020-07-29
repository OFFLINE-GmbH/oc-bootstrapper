<?php

namespace OFFLINE\Bootstrapper\October\Console;

use InvalidArgumentException;
use OFFLINE\Bootstrapper\October\DevEnvironment\Lando;
use OFFLINE\Bootstrapper\October\Util\ManageDirectory;
use OFFLINE\Bootstrapper\October\Util\UsesTemplate;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;

/**
 * Class InitCommand
 * @package OFFLINE\Bootstrapper\October\Console
 */
class InitCommand extends Command
{
    use UsesTemplate, ManageDirectory;

    const OPTION_DEV_BLANK = 'Do not set up an environment';
    const OPTION_DEV_LANDO = 'lando.dev development environment';

    /**
     * Configure the command options.
     *
     * @return void
     * @throws InvalidArgumentException
     */
    protected function configure()
    {
        $this
            ->setName('init')
            ->setDescription('Create a new October CMS project.')
            ->addArgument('directory', InputArgument::OPTIONAL, 'Name of the working directory', '.');
    }

    /**
     * Execute the command.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return mixed
     * @throws RuntimeException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');
        $question = new ChoiceQuestion(
            'Please select a dev environment to set up',
            [static::OPTION_DEV_BLANK, static::OPTION_DEV_LANDO],
            0
        );
        $dev = $helper->ask($input, $output, $question);

        $output->writeln('<info>Creating project directory...</info>');

        $dir = $this->pwd() . $input->getArgument('directory');

        $this->mkdir($dir);

        $output->writeln('<info>Updating template files...</info>');
        $this->updateTemplateFiles();

        $template = $this->getTemplate('october.yaml');
        $targetOctoberYaml = $dir . DS . 'october.yaml';

        $output->writeln('<info>Creating default october.yaml...</info>');

        if ($this->fileExists($targetOctoberYaml)) {
            $output->writeln('<comment>october.yaml already exists: ' . $targetOctoberYaml . '</comment>');
        } else {
            $this->copy($template, $targetOctoberYaml);
        }

        if ($dev === static::OPTION_DEV_LANDO) {
            $targetLandoYaml = $dir . DS . '.lando.yml';
            $question = new Question("What is the name of your Lando project?\n", 'october');
            $project = $helper->ask($input, $output, $question);
            if ($this->fileExists($targetLandoYaml)) {
                $output->writeln('<comment>.lando.yml already exists: ' . $targetLandoYaml . '</comment>');
            } else {
                $this->copy($this->getTemplate('lando.yml'), $targetLandoYaml);
            }
            $this->replaceVars($targetLandoYaml, ['name' => $project]);
            $this->replaceVars($targetOctoberYaml, ['name' => $project]);
            $this->setLandoConfig($targetOctoberYaml, $project);
        }

        $output->writeln('<comment>Done! Now edit your october.yaml and run october install.</comment>');

        return true;
    }

    /**
     * We already know Lando's DB credentials and the mailhog setup, so we can set them right away.
     */
    private function setLandoConfig(string $targetOctoberYaml, string $project)
    {
        $contents = file_get_contents($targetOctoberYaml);
        $contents = preg_replace_callback('/^app:\n\s+name: (?<name>[^\s]+).*\n\s+url: (?<url>[^\s]+)/m', function ($matches) use ($project) {
            $app = <<<EOF
app:
    name: %s
    url: %s
EOF;

            return sprintf($app, $project, 'http://' . $project . '.lndo.site');
        }, $contents);


        $contents = preg_replace_callback('/^database:\n(?:^[ ].*\n?)*$/m', function () {
            return <<<EOF
database:
    connection: mysql
    host: database
    port: 3306
    username: lamp
    password: lamp
    database: lamp

EOF;
        }, $contents);

        $contents = preg_replace_callback('/^mail:\n(?:^[ ].*\n?)*$/m', function () use ($project) {
        return <<<EOF
mail:
    host: mailhog 
    name: Sender Name
    address: sender@example.tld
    driver: smtp
    port: 1025
    encryption: ''

EOF;
        }, $contents);

        file_put_contents($targetOctoberYaml, $contents);
    }
}
