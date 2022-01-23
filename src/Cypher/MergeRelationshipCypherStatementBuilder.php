<?php

namespace TopicCards\Cypher;

use TopicCards\Import\RelationshipImportData;


class MergeRelationshipCypherStatementBuilder implements CypherStatementBuilderInterface
{
    protected RelationshipImportData $relationshipData;


    public function __construct(RelationshipImportData $relationshipData)
    {
        $this->relationshipData = $relationshipData;
    }


    public function getCypherStatement(): CypherStatement
    {
        $cypherStatement = new CypherStatement();

        if (!(isset($this->relationshipData->startNode) && isset($this->relationshipData->endNode))) {
            return $cypherStatement;
        }

        $startNodeLabelsStatement = (new LabelsCypherStatementBuilder($this->relationshipData->startNode->labels))->getCypherStatement();
        $endNodeLabelsStatement = (new LabelsCypherStatementBuilder($this->relationshipData->endNode->labels))->getCypherStatement();

        $startNodePropertiesStatement = (new PropertiesCypherStatementBuilder($this->relationshipData->startNode->properties,
            'start_'))->getCypherStatement();
        $endNodePropertiesStatement = (new PropertiesCypherStatementBuilder($this->relationshipData->endNode->properties,
            'end_'))->getCypherStatement();

        $cypherStatement
            ->setStatement(sprintf(
                'MATCH (startNode%s %s) MATCH (endNode%s %s) MERGE (startNode)-[r%s {uuid: {{ uuid }}}]->(endNode)',
                $startNodeLabelsStatement->getUnrenderedStatement(),
                $startNodePropertiesStatement->getUnrenderedStatement(),
                $endNodeLabelsStatement->getUnrenderedStatement(),
                $endNodePropertiesStatement->getUnrenderedStatement(),
                (new LabelsCypherStatementBuilder([$this->relationshipData->type]))->getCypherStatement()->getUnrenderedStatement()
            ))
            ->setParameter('uuid', $this->relationshipData->getProperty('uuid'))
            ->mergeParameters($startNodePropertiesStatement->getParameters())
            ->mergeParameters($endNodePropertiesStatement->getParameters());

        $setPropertiesStatement = (new SetPropertiesCypherStatementBuilder('r', $this->relationshipData->properties,
            true))->getCypherStatement();

        $cypherStatement
            ->append($setPropertiesStatement->getUnrenderedStatement())
            ->mergeParameters($setPropertiesStatement->getParameters());

        $cypherStatement->append(' RETURN r.uuid');

        return $cypherStatement;
    }
}