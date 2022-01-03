<?php

namespace TopicCards\Cypher;

use TopicCards\Import\RelationshipImportData;


class MergeRelationshipCypherStatement implements CypherStatementInterface
{
    protected bool $isGenerated = false;
    protected array $parameters;
    protected RelationshipImportData $relationshipData;
    protected string $statement = '';


    public function __construct(RelationshipImportData $relationshipData)
    {
        $this->relationshipData = $relationshipData;
    }


    protected function generateStatement(): void
    {
        if ($this->isGenerated) {
            return;
        }

        // TODO Move type to SET statements so that a change in type does not lead to duplicate relationships

        $this->statement = sprintf(
            'MATCH (startNode {uuid: $startUuid}) MATCH (endNode {uuid: $endUuid})' .
            ' MERGE (startNode)-[r%s {uuid: $uuid}]->(endNode)',
            (new LabelsCypherStatement([$this->relationshipData->type]))->getStatement()
        );

        $this->parameters = [
            'endUuid' => $this->relationshipData->endNode->getProperty('uuid')->values[0] ?? '',
            'startUuid' => $this->relationshipData->startNode->getProperty('uuid')->values[0] ?? '',
            'uuid' => $this->relationshipData->getProperty('uuid')
        ];

        $setPropertiesStatement = new SetPropertiesCypherStatement('r', $this->relationshipData->properties, true);

        $this->statement .= $setPropertiesStatement->getStatement();
        $this->parameters = array_merge($this->parameters, $setPropertiesStatement->getParameters());

        $this->statement .= ' RETURN r.uuid';

        $this->isGenerated = true;
    }


    public function getStatement(): string
    {
        $this->generateStatement();

        return $this->statement;
    }


    public function getParameters(): array
    {
        $this->generateStatement();

        return $this->parameters;
    }
}