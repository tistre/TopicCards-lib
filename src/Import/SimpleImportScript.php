<?php

namespace TopicCards\Import;

use Laudis\Neo4j\ClientBuilder;
use Laudis\Neo4j\Contracts\ClientInterface;
use Laudis\Neo4j\Contracts\TransactionInterface;
use Ramsey\Uuid\Uuid;
use TopicCards\Cypher\MergeNodeCypherStatementBuilder;
use TopicCards\Cypher\MergeRelationshipCypherStatementBuilder;


class SimpleImportScript
{
    protected string $driver;
    protected string $url;


    public function __construct(string $driver, string $url)
    {
        $this->driver = $driver;
        $this->url = $url;
    }


    protected function createClient(): ClientInterface
    {
        return ClientBuilder::create()
            ->withDriver($this->driver, $this->url, null, 0)
            ->withDefaultDriver($this->driver)
            ->build();
    }


    public function importAllFromFile(string $filename): void
    {
        $client = $this->createClient();
        $importDataObjects = new GraphXmlReader($filename);

        foreach ($importDataObjects as $importData) {
            if ($importData instanceof NodeImportData) {
                $this->importNode($client, $importData);
            } elseif ($importData instanceof RelationshipImportData) {
                $this->importRelationship($client, $importData);
            }
        }
    }


    protected function importNode(ClientInterface $client, NodeImportData $nodeData): void
    {
        $uuid = $this->getUuid($nodeData);

        $statement = (new MergeNodeCypherStatementBuilder($nodeData))->getCypherStatement();
        print_r($statement->getStatement()); echo "\n";

        $client->writeTransaction(static function (TransactionInterface $tsx) use ($statement) {
            $tsx->run($statement->getStatement(), $statement->getParameters());
        });

        printf("Imported node <%s>\n", $uuid);
    }


    protected function importRelationship(ClientInterface $client, RelationshipImportData $relationshipData): void
    {
        $uuid = $this->getUuid($relationshipData);

        $statement = (new MergeRelationshipCypherStatementBuilder($relationshipData))->getCypherStatement();
        print_r($statement->getStatement()); echo "\n";

        $client->writeTransaction(static function (TransactionInterface $tsx) use ($statement) {
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