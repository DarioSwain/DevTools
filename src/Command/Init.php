<?php

namespace DS\DevTools\Command;

use DS\DevTools\Repository\ConfigurationRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class Init extends Command
{
    protected function configure()
    {
        $this
            ->setName('init')
            ->setDescription('Configure or re-configure dev tools.')
            ->setHelp('This command allows you to setup dev tools.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln([
            'DevTools Configurator',
            '============',
            '',
        ]);

        $helper = $this->getHelper('question');
        $question = new Question('Please provide your Stash host name: ', null);
        $stashHost = $helper->ask($input, $output, $question);
        $question = new Question('Please provide your Stash user name: ', null);
        $stashUser = $helper->ask($input, $output, $question);
        $question = new Question('Please provide your Stash password: ', null);
        $question->setHidden(true);
        $question->setHiddenFallback(false);
        $stashPassword = $helper->ask($input, $output, $question);

        (new ConfigurationRepository())->save([
            'stash' => [
                'host' => $stashHost,
                'user' => $stashUser,
                'password' => $stashPassword
            ],
        ]);

        $output->writeln('DevTools was configured with provided settings, to re-configure - just re-run init command.');
    }
}
