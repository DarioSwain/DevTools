<?php

namespace DS\DevTools\Repository;

class ConfigurationRepository
{
    protected $storageLocation;
    /** @var array */
    protected $configuration;

    public function __construct($storageFilePath = null)
    {
        $this->storageLocation = ((string) ($storageFilePath ?? __DIR__.'/../../var/cache')).'/configuration.json';
        if (file_exists($this->storageLocation)) {
            $content = file_get_contents($this->storageLocation);
        } else {
            $content = null;
            $configurationFile = fopen($this->storageLocation, 'w');
            fclose($configurationFile);
        }

        $this->configuration = $content ? json_decode($content, true) : [];
    }

    public function getStashHost()
    {
        return $this->configuration['stash']['host'] ?? null;
    }

    public function getStashUser()
    {
        return $this->configuration['stash']['user'] ?? null;
    }

    public function getStashPassword()
    {
        return $this->configuration['stash']['password'] ?? null;
    }

    public function save($configuration)
    {
        $this->configuration = $configuration;
        $file = fopen($this->storageLocation, 'w');
        fwrite($file, json_encode($configuration));
        fclose($file);
    }
}
