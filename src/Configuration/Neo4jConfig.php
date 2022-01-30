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
        return ClientBuilder::create()
            ->withDriver($this->getDriverAlias(), $this->getDriverUrl())
            ->withDefaultDriver($this->getDriverAlias())
            ->build();
    }
}