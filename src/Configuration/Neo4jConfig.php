<?php

namespace StrehleDe\TopicCards\Configuration;

use Laudis\Neo4j\ClientBuilder;
use Laudis\Neo4j\Contracts\ClientInterface;


class Neo4jConfig
{
    protected array $configArray;


    public function __construct(array $configArray)
    {
        $this->configArray = $configArray;
    }


    public function getDriverAlias(): string
    {
        return $this->configArray['driver']['alias'] ?? '';
    }


    public function getDriverUrl(): string
    {
        return $this->configArray['driver']['url'] ?? '';
    }


    public function getClient(): ClientInterface
    {
        static $client = false;

        if (!is_object($client)) {
            $client = ClientBuilder::create()
                ->withDriver($this->getDriverAlias(), $this->getDriverUrl())
                ->withDefaultDriver($this->getDriverAlias())
                ->build();
        }

        return $client;
    }
}