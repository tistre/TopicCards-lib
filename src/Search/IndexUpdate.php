<?php

namespace StrehleDe\TopicCards\Search;

use Laudis\Neo4j\Contracts\ClientInterface;
use Laudis\Neo4j\Types\CypherMap;
use StrehleDe\TopicCards\Configuration\Configuration;
use StrehleDe\TopicCards\Cypher\Converter;
use StrehleDe\TopicCards\Cypher\NodeToIndexCypherStatementBuilder;
use StrehleDe\TopicCards\Data\NodeData;
use StrehleDe\TopicCards\Data\PropertyData;


class IndexUpdate
{
    public static function updateNode(string $uuid, Configuration $configuration): void
    {
        $indexData = self::getNodeIndexData($uuid, $configuration->getNeo4jConfig()->getClient());

        $elasticsearchConfig = $configuration->getElasticsearchConfig();

        $elasticsearchConfig->getClient()->index([
            'index' => $elasticsearchConfig->getIndex(),
            'id' => $uuid,
            'body' => $indexData
        ]);
    }


    public static function getNodeIndexData(string $uuid, ClientInterface $neo4jClient): array
    {
        $nodeData = (new NodeData())
            ->addProperty(
                new PropertyData('uuid', $uuid)
            );

        $statement = (new NodeToIndexCypherStatementBuilder($nodeData))->getCypherStatement();

        $rows = $neo4jClient->run(
            $statement->getStatement(),
            $statement->getParameters()
        );

        /** @var CypherMap $row */
        $row = $rows[0];

        $map = $row->getAsCypherMap('n');

        $indexData = [];

        foreach ($map as $key => $value) {
            $indexData[$key] = Converter::neo4jToScalar($value);
        }

        return $indexData;
    }
}