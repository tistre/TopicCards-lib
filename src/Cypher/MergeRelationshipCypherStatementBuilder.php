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

        $startNodeWhereStatement =
            (new WherePropertiesCypherStatementBuilder(
                'startNode',
                $this->relationshipData->getStartNode()->getProperties(),
                'start_'
            ))
                ->getCypherStatement();

        $endNodeWhereStatement =
            (new WherePropertiesCypherStatementBuilder(
                'endNode',
                $this->relationshipData->getEndNode()->getProperties(),
                'end_'
            ))
                ->getCypherStatement();

        $cypherStatement
            ->setStatement(sprintf(
                'MATCH (startNode%s) %s MATCH (endNode%s) %s MERGE (startNode)-[r%s]->(endNode)',
                $startNodeLabelsStatement->getUnrenderedStatement(),
                $startNodeWhereStatement->getUnrenderedStatement(),
                $endNodeLabelsStatement->getUnrenderedStatement(),
                $endNodeWhereStatement->getUnrenderedStatement(),
                (new LabelsCypherStatementBuilder([$this->relationshipData->getType()]))->getCypherStatement()->getUnrenderedStatement()
            ))
            ->mergeParameters($startNodeWhereStatement->getParameters())
            ->mergeParameters($endNodeWhereStatement->getParameters());

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

        $cypherStatement->append(' RETURN id(r)');

        return $cypherStatement;
    }
}