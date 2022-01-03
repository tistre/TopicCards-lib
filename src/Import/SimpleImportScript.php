<?php

namespace TopicCards\Import;

use Laudis\Neo4j\Contracts\ClientInterface;
use Laudis\Neo4j\Contracts\TransactionInterface;
use Ramsey\Uuid\Uuid;
use TopicCards\Cypher\CypherStatement;
use TopicCards\Cypher\CypherUtils;
use TopicCards\Cypher\MergeNodeCypherStatement;
use TopicCards\Cypher\MergeRelationshipCypherStatement;


class SimpleImportScript
{
    protected ClientInterface $client;


    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
    }


    public function importAllFromFile(string $filename): void
    {
        $importDataObjects = new GraphXmlReader($filename);

        foreach ($importDataObjects as $importData) {
            if ($importData instanceof NodeImportData) {
                $this->importNode($importData);
            } elseif ($importData instanceof RelationshipImportData) {
                $this->importRelationship($importData);
            }
        }
    }


    protected function importNode(NodeImportData $nodeData): void
    {
        $uuid = $this->getUuid($nodeData);

        $statement = new MergeNodeCypherStatement($nodeData);
        print_r($statement->getStatement()); echo "\n";

        $this->client->writeTransaction(static function (TransactionInterface $tsx) use ($statement) {
            $tsx->run($statement->getStatement(), $statement->getParameters());
        });

        printf("Imported node <%s>\n", $uuid);
    }


    protected function importRelationship(RelationshipImportData $relationshipData): void
    {
        $uuid = $this->getUuid($relationshipData);

        $statement = new MergeRelationshipCypherStatement($relationshipData);
        print_r($statement->getStatement()); echo "\n";

        $this->client->writeTransaction(static function (TransactionInterface $tsx) use ($statement) {
            $tsx->run($statement->getStatement(), $statement->getParameters());
        });

        printf("Imported relationship <%s>\n", $uuid);
    }


    protected function getUuid(ImportData $importData): string
    {
        // If a uuid property already exists, return its value

        $propertyData = $importData->getProperty('uuid');

        if (!is_null($propertyData)) {
            return $propertyData->values[0];
        }

        // Otherwise create and return a new uuid property

        $uuid = Uuid::uuid4();

        $propertyData = new PropertyImportData();
        $propertyData->name = 'uuid';
        $propertyData->values[] = (string)$uuid;

        $importData->properties[] = $propertyData;

        return $uuid;
    }
}