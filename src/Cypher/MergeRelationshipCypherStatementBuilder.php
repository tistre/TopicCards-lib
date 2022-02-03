<?php

namespace StrehleDe\TopicCards\Cypher;

use StrehleDe\TopicCards\Data\RelationshipData;


class MergeRelationshipCypherStatementBuilder implements CypherStatementBuilderInterface
{
    protected RelationshipData $relationshipData;


    public function __construct(RelationshipData $relationshipData)
    {
        $this->relationshipData = $relationshipData;
    }


    public function getCypherStatement(): CypherStatement
    {
        $cypherStatement = new CypherStatement();

        if (empty($this->relationshipData->getStartNode()) || empty($this->relationshipData->getEndNode())) {
            return $cypherStatement;
        }

        $startNodeLabelsStatement =
            (new LabelsCypherStatementBuilder(
                $this->relationshipData->getStartNode()->getLabels()
            ))
                ->getCypherStatement();

        $endNodeLabelsStatement =
            (new LabelsCypherStatementBuilder(
                $this->relationshipData->getEndNode()->getLabels()
            ))
                ->getCypherStatement();

        $startNodePropertiesStatement =
            (new PropertyMapCypherStatementBuilder(
                $this->relationshipData->getStartNode()->getProperties(),
                'start_'
            ))
                ->getCypherStatement();

        $endNodePropertiesStatement =
            (new PropertyMapCypherStatementBuilder(
                $this->relationshipData->getEndNode()->getProperties(),
                'end_'
            ))
                ->getCypherStatement();

        $cypherStatement
            ->setStatement(sprintf(
                'MATCH (startNode%s %s) MATCH (endNode%s %s) MERGE (startNode)-[r%s {uuid: {{ uuid }}}]->(endNode)',
                $startNodeLabelsStatement->getUnrenderedStatement(),
                $startNodePropertiesStatement->getUnrenderedStatement(),
                $endNodeLabelsStatement->getUnrenderedStatement(),
                $endNodePropertiesStatement->getUnrenderedStatement(),
                (new LabelsCypherStatementBuilder([$this->relationshipData->getType()]))->getCypherStatement()->getUnrenderedStatement()
            ))
            ->setParameter('uuid', $this->relationshipData->getProperty('uuid'))
            ->mergeParameters($startNodePropertiesStatement->getParameters())
            ->mergeParameters($endNodePropertiesStatement->getParameters());

        $setPropertiesStatement =
            (new SetPropertiesCypherStatementBuilder(
                'r',
                $this->relationshipData->getProperties(),
                true
            ))
                ->getCypherStatement();

        $cypherStatement
            ->append($setPropertiesStatement->getUnrenderedStatement())
            ->mergeParameters($setPropertiesStatement->getParameters());

        $cypherStatement->append(' RETURN r.uuid');

        return $cypherStatement;
    }
}