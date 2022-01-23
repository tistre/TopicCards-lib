<?php

namespace StrehleDe\TopicCards\Import;

use Laudis\Neo4j\ClientBuilder;
use Laudis\Neo4j\Contracts\ClientInterface;
use Laudis\Neo4j\Contracts\TransactionInterface;
use Ramsey\Uuid\Uuid;
use StrehleDe\TopicCards\Cypher\MergeNodeCypherStatementBuilder;
use StrehleDe\TopicCards\Cypher\MergeRelationshipCypherStatementBuilder;


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


    public function importFile(string $filename): void
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


    public function convertFileToCypher(string $filename): void
    {
        $client = $this->createClient();
        $importDataObjects = new GraphXmlReader($filename);

        foreach ($importDataObjects as $importData) {
            if ($importData instanceof NodeImportData) {
                echo $this->nodeToCypher($client, $importData);
            } elseif ($importData instanceof RelationshipImportData) {
                echo $this->relationshipToCypher($client, $importData);
            }
        }
    }


    protected function importNode(ClientInterface $client, NodeImportData $nodeData): void
    {
        $uuid = $this->getUuid($nodeData);

        $statement = (new MergeNodeCypherStatementBuilder($nodeData))->getCypherStatement();
        print_r($statement->getStatement(false)); echo "\n";

        $client->writeTransaction(static function (TransactionInterface $tsx) use ($statement) {
            $tsx->run($statement->getStatement(), $statement->getParameters());
        });

        printf("Imported node <%s>\n", $uuid);
    }


    protected function importRelationship(ClientInterface $client, RelationshipImportData $relationshipData): void
    {
        $uuid = $this->getUuid($relationshipData);

        $statement = (new MergeRelationshipCypherStatementBuilder($relationshipData))->getCypherStatement();
        print_r($statement->getStatement(false)); echo "\n";

        $client->writeTransaction(static function (TransactionInterface $tsx) use ($statement) {
            $tsx->run($statement->getStatement(), $statement->getParameters());
        });

        printf("Imported relationship <%s>\n", $uuid);
    }


    protected function nodeToCypher(ClientInterface $client, NodeImportData $nodeData): string
    {
        $uuid = $this->getUuid($nodeData);

        $statement = (new MergeNodeCypherStatementBuilder($nodeData))->getCypherStatement();
        $cypher = $statement->getStatement(false);

        return $cypher ? $cypher . ";\n" : '';
    }


    protected function relationshipToCypher(ClientInterface $client, RelationshipImportData $relationshipData): string
    {
        $uuid = $this->getUuid($relationshipData);

        $statement = (new MergeRelationshipCypherStatementBuilder($relationshipData))->getCypherStatement();
        $cypher = $statement->getStatement(false);

        return $cypher ? $cypher . ";\n" : '';
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