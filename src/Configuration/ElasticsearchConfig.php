<?php

namespace StrehleDe\TopicCards\Configuration;

use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;


class ElasticsearchConfig
{
    protected array $configArray;


    public function __construct(array $configArray)
    {
        $this->configArray = $configArray;
    }


    public function getHosts(): array
    {
        return $this->configArray['hosts'] ?? [];
    }


    public function getIndex(): string
    {
        return $this->configArray['index'] ?? '';
    }


    public function getClient(): Client
    {
        return ClientBuilder::create()
            ->setHosts($this->getHosts())
            ->build();
    }
}