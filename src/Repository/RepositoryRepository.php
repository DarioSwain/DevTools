<?php

namespace DS\DevTools\Repository;

class RepositoryRepository
{
    protected $storageLocation;
    /** @var array */
    protected $repositories;

    public function __construct($storageFilePath = null)
    {
        $this->storageLocation = ((string) ($storageFilePath ?? __DIR__.'/../../var/cache')).'/repositories.json';
        if (file_exists($this->storageLocation)) {
            $content = file_get_contents($this->storageLocation);
        } else {
            $content = null;
            $repositoriesFile = fopen($this->storageLocation, 'w');
            fclose($repositoriesFile);
        }

        $this->repositories = $content ? json_decode($content, true) : [];
    }

    public function save($repository)
    {
        $repository = $this->findByKeyAndSlug($repository['projectKey'], $repository['repositorySlug']);
        //TODO: Remove if exists.

        $this->repositories[] = $repository;
        $repositoriesFile = fopen($this->storageLocation, 'w');
        fwrite($repositoriesFile, json_encode($this->repositories));
        fclose($repositoriesFile);
    }

    public function findByKeyAndSlug($projectKey, $repositorySlug)
    {
        foreach ($this->repositories as $repository) {
            if ($repository['projectKey'] === $projectKey && $repository['repositorySlug'] === $repositorySlug) {
                return $repository;
            }
        }

        return null;
    }

    public function findByForkKeyAndSlug($projectKey, $repositorySlug)
    {
        foreach ($this->repositories as $repository) {
            foreach ($repository['forks'] as $fork) {
                if (($fork['projectKey'] === $projectKey || '~'.$fork['projectKey'] === $projectKey) && $fork['repositorySlug'] === $repositorySlug) {
                    return $repository;
                }
            }
        }

        return null;
    }
}
