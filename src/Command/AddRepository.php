<?php

namespace DS\DevTools\Command;

use DS\DevTools\Repository\RepositoryRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

class AddRepository  extends Command
{
    protected function configure()
    {
        $this
            ->setName('repo:new')
            ->setDescription('Registers a new Stash repository.')
            ->setHelp('This command allows you to register a new Stash repository.')
            ->addArgument('projectKey', InputArgument::REQUIRED, 'Stash repo project key.')
            ->addArgument('repositorySlug', InputArgument::REQUIRED, 'Stash repo slug.')
            ->addArgument('alias', InputArgument::REQUIRED, 'Alias for Stash repo.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $projectKey = $input->getArgument('projectKey');
        $repositorySlug = $input->getArgument('repositorySlug');
        $alias = $input->getArgument('alias');

        $repository = [
            'alias' => $alias,
            'projectKey' => $projectKey,
            'repositorySlug' => $repositorySlug,
            'forks' => [],
            'reviewers' => [],
        ];

        $output->writeln([
            'Repository Creator',
            '============',
            '',
        ]);

        $output->writeln('projectKey: '.$projectKey);
        $output->writeln('repositorySlug: '.$repositorySlug);

        $helper = $this->getHelper('question');

        $question = new ConfirmationQuestion('Would you like to add a fork? [y] ', true);
        if ($helper->ask($input, $output, $question)) {
            $output->writeln([
                '',
                'Fork Creator',
                '============',
                '',
            ]);
            while(true) {
                $question = new Question('Please enter the fork projectKey ['.$projectKey.']: ', $projectKey);
                $forkProjectKey = $helper->ask($input, $output, $question);
                $question = new Question('Please enter the fork repositorySlug ['.$repositorySlug.']: ', $repositorySlug);
                $forkRepositorySlug = $helper->ask($input, $output, $question);

                $repository['forks'][] = [
                    'projectKey' => $forkProjectKey,
                    'repositorySlug' => $forkRepositorySlug,
                ];

                $output->writeln('Fork Added.');
                $output->writeln('projectKey: '.$forkProjectKey);
                $output->writeln('repositorySlug: '.$forkRepositorySlug);

                $question = new ConfirmationQuestion('Would you like to add another fork? [n] ', false);
                if (!$helper->ask($input, $output, $question)) {
                    break;
                }
            }
        }

        $output->writeln([
            '',
            'Reviewers Creator',
            '============',
            '',
        ]);
        while(true) {
            $question = new Question('Please enter the reviewer name: ', '');
            $reviewer = $helper->ask($input, $output, $question);
            $repository['reviewers'][] = $reviewer;

            $output->writeln('Reviewer Added.');
            $output->writeln('reviewer: '.$reviewer);

            $question = new ConfirmationQuestion('Would you like to add another reviewer? [y] ', true);
            if (!$helper->ask($input, $output, $question)) {
                break;
            }
        }

        (new RepositoryRepository())->save($repository);
    }
}
