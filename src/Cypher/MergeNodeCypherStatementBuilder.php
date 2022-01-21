<?php

namespace TopicCards\Cypher;

use TopicCards\Import\NodeImportData;


class MergeNodeCypherStatementBuilder implements CypherStatementBuilderInterface
{
    protected array $parameters;
    protected NodeImportData $nodeData;
    protected string $statement = '';


    public function __construct(NodeImportData $nodeData)
    {
        $this->nodeData = $nodeData;
    }


    public function getCypherStatement(): CypherStatement
    {
        $cypherStatement = new CypherStatement();

        $cypherStatement->setStatement('MERGE (n {uuid: {{ uuid }}})');

        if (count($this->nodeData->labels) > 0) {
            $cypherStatement->append(sprintf(
                ' SET n%s',
                (new LabelsCypherStatementBuilder($this->nodeData->labels))->getCypherStatement()->getStatement()
            ));
        }

        $cypherStatement->setParameter('uuid', $this->nodeData->getProperty('uuid'));

        $setPropertiesStatement = (new SetPropertiesCypherStatementBuilder(
            'n',
            $this->nodeData->properties,
            true
        ))->getCypherStatement();

        $cypherStatement->append($setPropertiesStatement->getStatement());
        $cypherStatement->mergeParameters($setPropertiesStatement->getParameters());

        $cypherStatement->append(' RETURN n.uuid');

        return $cypherStatement;
    }
}