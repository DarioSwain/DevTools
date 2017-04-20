<?php

namespace DS\DevTools\Command;

use DS\DevTools\Repository\ConfigurationRepository;
use DS\DevTools\Repository\RepositoryRepository;
use GitWrapper\GitWrapper;
use StashAPILib\StashAPIClient;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

class PublishPullRequest extends Command
{
    protected function configure()
    {
        $this
            ->setName('pr')
            ->setDescription('Publish a new PR to Stash repository.')
            ->setHelp('This command allows you to publish a new PR to Stash repository.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        //TODO: Verify GIT version. Should not be 1.9.1

        $helper = $this->getHelper('question');
        $repository = new RepositoryRepository();
        $gitWrapper = new GitWrapper();
        $currentDirectory = getcwd();
        $git = $gitWrapper->workingCopy($currentDirectory);

        preg_match('/ssh.*\/(.*)\/(.*).git$/', $git->getRemote('origin')['fetch'], $matches);
        list ($fullUrl, $projectKey, $repositorySlug) = $matches;

        $output->writeln([
            'Pull request publisher',
            '============',
            '',
        ]);

        $repo = $repository->findByForkKeyAndSlug($projectKey, $repositorySlug);

        $reviewers = array_map(function ($value) { return ["user" => ["name" => $value]]; }, $repo['reviewers']);

        if (null === $repo) {
            $output->writeln('Impossible to find proper repository by projectKey ('.$projectKey.') and repository slug ('.$repositorySlug.')');
            return;
        }

        $fromBranch = $git->getBranches()->head();
        $question = new Question('Please enter "from" branch: [current:'.$fromBranch.']: ', $fromBranch);
        $fromBranch = $helper->ask($input, $output, $question);

        $question = new Question('Please enter "to" branch: [current:develop]: ', 'develop');
        $toBranch = $helper->ask($input, $output, $question);

        $output->writeln([
            '',
            'Initialize Pull Request...',
            $projectKey.':'.$repositorySlug.' ('.$fromBranch.') -> '.$repo['projectKey'].':'.$repo['repositorySlug'].' ('.$toBranch.')',
            '',
            'Reviewers: '.implode(', ', $repo['reviewers']),
            ''
        ]);

        $title = trim($git->run(['log -1 --pretty=%B'])->getOutput());

        $pullRequestData = [];
        while (true) {
            $question = new Question('Please enter PR title ['.$title.']: ', $title);
            $pullRequestData['title'] = $helper->ask($input, $output, $question);

            $question = new ConfirmationQuestion('Is it bug fix? [n]: ', false);
            $isBugFix = $helper->ask($input, $output, $question);
            $question = new ConfirmationQuestion('Is it new feature? [n]: ', false);
            $isNewFeature = $helper->ask($input, $output, $question);
            $question = new ConfirmationQuestion('Are those changes break BC? [n]: ', false);
            $isBcBreak = $helper->ask($input, $output, $question);
            $question = new ConfirmationQuestion('Is it include deprecations? [n]: ', false);
            $isIncludeDeprecations = $helper->ask($input, $output, $question);
            $question = new ChoiceQuestion('Which priority should be set? [major]: ', ['minor', 'major', 'critical', 'blocker'], 'major');
            $priority = $helper->ask($input, $output, $question);
            $question = new Question('Please add ticket numbers [none]: ', 'none');
            $tickets = $helper->ask($input, $output, $question);
            $question = new Question('Please add custom PR description ['.$pullRequestData['title'].']: ', $pullRequestData['title']);
            $customDescriptionLine = $helper->ask($input, $output, $question);

            $description = <<<EOD
| Q             | A
| ------------- | ---
| Bug fix?      | {$this->boolToYesNoString($isBugFix)}
| New feature?  | {$this->boolToYesNoString($isNewFeature)}
| BC breaks?    | {$this->boolToYesNoString($isBcBreak)}
| Deprecations? | {$this->boolToYesNoString($isIncludeDeprecations)}
| Priority?     | {$priority}
| Fixed tickets | {$tickets}

{$customDescriptionLine}

EOD;
            $pullRequestData['description'] = $description;

            $output->writeln([
                '',
                'Pull request details',
                '============',
                'Title: '.$pullRequestData['title'],
                '',
                'Description:'
            ]);
            $output->write($description);

            $question = new ConfirmationQuestion('Is PR correct and can be published? [y]: ', true);
            if ($helper->ask($input, $output, $question)) {
                break;
            } else {
                $output->writeln('Re-configure PR...');
            }
        }

        $configurationRepository = new ConfigurationRepository();

        $stashClient = new StashAPIClient(
            'https://'.$configurationRepository->getStashHost(),
            $configurationRepository->getStashUser(),
            $configurationRepository->getStashPassword()
        );

        $pr = $stashClient->getPullRequest()->createPullRequest(
            [
                "title" => $pullRequestData['title'],
                "description" => $pullRequestData['description'],
                "state" => "OPEN",
                "open" => true,
                "closed" => false,
                "fromRef" => [
                    "id" => "refs/heads/".$fromBranch,
                    "repository"=> [
                        "slug" => $repositorySlug,
                        "name" => null,
                        "project" => [
                            "key" => $projectKey
                        ],
                    ],
                ],
                "toRef" => [
                    "id" => "refs/heads/".$toBranch,
                    "repository" => [
                        "slug" => $repo['repositorySlug'],
                        "name" => null,
                        "project" => [
                            "key" => $repo['projectKey']
                        ],
                    ],
                ],
                "locked" => false,
                "reviewers" => $reviewers
            ],
            $repo['projectKey'],
            $repo['repositorySlug']
        );

        var_dump($pr);
        $output->writeln([
            '',
            'Pull request successfully created!',
            $pr->links->self[0]->href,
            ''
        ]);
    }

    protected function boolToYesNoString($value)
    {
        return $value ? 'yes' : 'no';
    }
}
