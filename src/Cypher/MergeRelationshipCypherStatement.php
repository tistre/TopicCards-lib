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

        $startNodePropertiesStatement = new PropertiesCypherStatement($this->relationshipData->startNode->properties, 'start_');
        $endNodePropertiesStatement = new PropertiesCypherStatement($this->relationshipData->endNode->properties, 'end_');

        $this->statement = sprintf(
            'MATCH (startNode %s) MATCH (endNode %s) MERGE (startNode)-[r%s {uuid: $uuid}]->(endNode)',
            $startNodePropertiesStatement->getStatement(),
            $endNodePropertiesStatement->getStatement(),
            (new LabelsCypherStatement([$this->relationshipData->type]))->getStatement()
        );

        $this->parameters = array_merge(
            ['uuid' => $this->relationshipData->getProperty('uuid')],
            $startNodePropertiesStatement->getParameters(),
            $endNodePropertiesStatement->getParameters()
        );

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