<?php

namespace StrehleDe\TopicCards\Cypher;

use StrehleDe\TopicCards\Data\NodeData;


class CreateNodeCypherStatementBuilder implements CypherStatementBuilderInterface
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

        $cypherStatement->setStatement('CREATE (n');

        if (count($this->nodeData->getLabels()) > 0) {
            $cypherStatement->append(
                (new LabelsCypherStatementBuilder($this->nodeData->getLabels()))->getCypherStatement()->getUnrenderedStatement()
            );
        }

        $setPropertiesStatement = (new PropertyMapCypherStatementBuilder(
            $this->nodeData->getProperties()))->getCypherStatement();

        $cypherStatement->append(' ' . $setPropertiesStatement->getUnrenderedStatement());
        $cypherStatement->mergeParameters($setPropertiesStatement->getParameters());

        $cypherStatement->append(') RETURN id(n)');

        return $cypherStatement;
    }
}