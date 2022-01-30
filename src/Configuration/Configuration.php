<?php

namespace StrehleDe\TopicCards\Configuration;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Yaml\Yaml;


class Configuration implements ConfigurationInterface
{
    protected array $configArray;
    protected string $configFilePath;


    /**
     * @param string $configFilePath Full path to YAML config file. Pass an empty string if you want to provide an array instead.
     * @param array $configArray Config array. Only used if $configFilePath is an empty string.
     */
    public function __construct(string $configFilePath, array $configArray = [])
    {
        $this->configArray = $configArray;
        $this->configFilePath = $configFilePath;
    }


    protected function getRawConfigArray(): array
    {
        if (empty($this->configFilePath)) {
            return $this->configArray;
        }

        return Yaml::parse(
            file_get_contents($this->configFilePath)
        );
    }


    public function getAll(): array
    {
        static $config = false;

        if (!is_array($config)) {
            $config = (new Processor())->processConfiguration(
                $this,
                [$this->getRawConfigArray()]
            );
        }

        return $config;
    }


    public function getElasticsearchConfig(): ElasticsearchConfig
    {
        static $elasticsearchConfig = false;

        if (!is_object($elasticsearchConfig)) {
            $elasticsearchConfig = new ElasticsearchConfig($this->getAll()['elasticsearch']);
        }

        return $elasticsearchConfig;
    }


    public function getNeo4jConfig(): Neo4jConfig
    {
        static $neo4jConfig = false;

        if (!is_object($neo4jConfig)) {
            $neo4jConfig = new Neo4jConfig($this->getAll()['neo4j']);
        }

        return $neo4jConfig;
    }


    /**
     * @inheritDoc
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('config');

        $treeBuilder->getRootNode()
            ->children()
                ->arrayNode('elasticsearch')
                    ->children()
                        ->arrayNode('hosts')
                            ->scalarPrototype()->end()
                        ->end()
                        ->scalarNode('index')->end()
                    ->end()
                ->end()
                ->arrayNode('neo4j')
                    ->children()
                        ->arrayNode('driver')
                            ->children()
                                ->scalarNode('alias')->end()
                                ->scalarNode('url')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}