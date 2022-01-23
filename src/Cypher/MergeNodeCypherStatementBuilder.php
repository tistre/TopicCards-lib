<?php

namespace StrehleDe\TopicCards\Cypher;

use StrehleDe\TopicCards\Data\NodeData;


class MergeNodeCypherStatementBuilder implements CypherStatementBuilderInterface
{
    protected array $parameters;
    protected NodeData $nodeData;
    protected string $statement = '';
    protected bool $replaceAll = false;


    public function __construct(NodeData $nodeData, bool $replaceAll = false)
    {
        $this->nodeData = $nodeData;
        $this->replaceAll = $replaceAll;
    }


    public function getCypherStatement(): CypherStatement
    {
        $cypherStatement = new CypherStatement();

        $cypherStatement->setStatement('MERGE (n {uuid: {{ uuid }}})');

        if (count($this->nodeData->getLabels()) > 0) {
            $cypherStatement->append(sprintf(
                ' SET n%s',
                (new LabelsCypherStatementBuilder($this->nodeData->getLabels()))->getCypherStatement()->getUnrenderedStatement()
            ));
        }

        $cypherStatement->setParameter('uuid', $this->nodeData->getProperty('uuid'));

        $setPropertiesStatement = (new SetPropertiesCypherStatementBuilder(
            'n',
            $this->nodeData->getProperties(),
            $this->replaceAll
        ))->getCypherStatement();

        $cypherStatement->append($setPropertiesStatement->getUnrenderedStatement());
        $cypherStatement->mergeParameters($setPropertiesStatement->getParameters());

        $cypherStatement->append(' RETURN n.uuid');

        return $cypherStatement;
    }
}