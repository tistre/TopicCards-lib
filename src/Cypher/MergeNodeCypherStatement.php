<?php

namespace TopicCards\Cypher;

use TopicCards\Import\NodeImportData;


class MergeNodeCypherStatement implements CypherStatementInterface
{
    protected bool $isGenerated = false;
    protected array $parameters;
    protected NodeImportData $nodeData;
    protected string $statement = '';


    public function __construct(NodeImportData $nodeData)
    {
        $this->nodeData = $nodeData;
    }


    protected function generateStatement(): void
    {
        if ($this->isGenerated) {
            return;
        }

        $this->statement = 'MERGE (n {uuid: $uuid})';

        if (count($this->nodeData->labels) > 0) {
            $this->statement .= sprintf(
                ' SET n%s',
                (new LabelsCypherStatement($this->nodeData->labels))->getStatement()
            );
        }

        $this->parameters = ['uuid' => $this->nodeData->getProperty('uuid')];

        $setPropertiesStatement = new SetPropertiesCypherStatement('n', $this->nodeData->properties, true);

        $this->statement .= $setPropertiesStatement->getStatement();
        $this->parameters = array_merge($this->parameters, $setPropertiesStatement->getParameters());

        $this->statement .= ' RETURN n.uuid';

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