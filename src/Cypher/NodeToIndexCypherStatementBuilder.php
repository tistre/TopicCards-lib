<?php

namespace StrehleDe\TopicCards\Cypher;

use StrehleDe\TopicCards\Data\NodeData;


class NodeToIndexCypherStatementBuilder implements CypherStatementBuilderInterface
{
    protected array $parameters;
    protected NodeData $nodeData;
    protected string $statement = '';


    public function __construct(NodeData $nodeData)
    {
        $this->nodeData = $nodeData;
    }


    public function getCypherStatement(): CypherStatement
    {
        $cypherStatement = new CypherStatement();

        $cypherStatement->setStatement('MATCH (n {uuid: {{ uuid }}}) RETURN n {_label: labels(n), .*}');

        $cypherStatement->setParameter('uuid', $this->nodeData->getProperty('uuid')->getValue());

        return $cypherStatement;
    }
}