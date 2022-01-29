<?php

namespace StrehleDe\TopicCards\Configuration;


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
}