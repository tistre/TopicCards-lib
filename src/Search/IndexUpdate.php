<?php

namespace StrehleDe\TopicCards\Search;

use Laudis\Neo4j\Types\CypherMap;
use StrehleDe\TopicCards\Configuration\Configuration;
use StrehleDe\TopicCards\Cypher\Converter;


class IndexUpdate
{
    public static function updateNode(string $uuid, Configuration $configuration): void
    {
        $indexData = self::getNodeIndexData($uuid, $configuration);

        $elasticsearchConfig = $configuration->getElasticsearchConfig();

        $elasticsearchConfig->getClient()->index([
            'index' => $elasticsearchConfig->getIndex(),
            'id' => $uuid,
            'body' => $indexData
        ]);
    }


    public static function getNodeIndexData(string $uuid, Configuration $configuration): array
    {
        $rows = $configuration->getNeo4jConfig()->getClient()->run(
            $configuration->getAll()['indexing']['node_query'],
            ['uuid' => $uuid]
        );

        if (count($rows) < 1) {
            return [];
        }

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