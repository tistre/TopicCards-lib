<?php

namespace StrehleDe\TopicCards\Configuration;


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
}