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

        $startNodeLabelsStatement = new LabelsCypherStatement($this->relationshipData->startNode->labels);
        $endNodeLabelsStatement = new LabelsCypherStatement($this->relationshipData->endNode->labels);

        $startNodePropertiesStatement = new PropertiesCypherStatement($this->relationshipData->startNode->properties, 'start_');
        $endNodePropertiesStatement = new PropertiesCypherStatement($this->relationshipData->endNode->properties, 'end_');

        $this->statement = sprintf(
            'MATCH (startNode%s %s) MATCH (endNode%s %s) MERGE (startNode)-[r%s {uuid: $uuid}]->(endNode)',
            $startNodeLabelsStatement->getStatement(),
            $startNodePropertiesStatement->getStatement(),
            $endNodeLabelsStatement->getStatement(),
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