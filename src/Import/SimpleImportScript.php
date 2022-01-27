<?php

namespace StrehleDe\TopicCards\Import;

use Laudis\Neo4j\ClientBuilder;
use Laudis\Neo4j\Contracts\ClientInterface;
use Laudis\Neo4j\Contracts\TransactionInterface;
use Ramsey\Uuid\Uuid;
use StrehleDe\TopicCards\Cypher\MergeNodeCypherStatementBuilder;
use StrehleDe\TopicCards\Cypher\MergeRelationshipCypherStatementBuilder;
use StrehleDe\TopicCards\Data\Data;
use StrehleDe\TopicCards\Data\NodeData;
use StrehleDe\TopicCards\Data\PropertyData;
use StrehleDe\TopicCards\Data\RelationshipData;


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
            ->withDriver($this->driver, $this->url)
            ->withDefaultDriver($this->driver)
            ->build();
    }


    public function importFile(string $filename): void
    {
        $client = $this->createClient();
        $dataObjects = new GraphXmlReader($filename);

        foreach ($dataObjects as $importData) {
            if ($importData instanceof NodeData) {
                $this->importNode($client, $importData);
            } elseif ($importData instanceof RelationshipData) {
                $this->importRelationship($client, $importData);
            }
        }
    }


    public function convertFileToCypher(string $filename): void
    {
        $dataObjects = new GraphXmlReader($filename);

        foreach ($dataObjects as $importData) {
            if ($importData instanceof NodeData) {
                echo $this->nodeToCypher($importData);
            } elseif ($importData instanceof RelationshipData) {
                echo $this->relationshipToCypher($importData);
            }
        }
    }


    protected function importNode(ClientInterface $client, NodeData $nodeData): void
    {
        $uuid = $nodeData->generateUuid();

        $statement = (new MergeNodeCypherStatementBuilder($nodeData, true))->getCypherStatement();
        print_r($statement->getStatement(false)); echo "\n";

        $client->writeTransaction(static function (TransactionInterface $tsx) use ($statement) {
            $tsx->run($statement->getStatement(), $statement->getParameters());
        });

        printf("Imported node <%s>\n", $uuid);
    }


    protected function importRelationship(ClientInterface $client, RelationshipData $relationshipData): void
    {
        $uuid = $relationshipData->generateUuid();

        $statement = (new MergeRelationshipCypherStatementBuilder($relationshipData))->getCypherStatement();
        print_r($statement->getStatement(false)); echo "\n";

        $client->writeTransaction(static function (TransactionInterface $tsx) use ($statement) {
            $tsx->run($statement->getStatement(), $statement->getParameters());
        });

        printf("Imported relationship <%s>\n", $uuid);
    }


    protected function nodeToCypher(NodeData $nodeData): string
    {
        $nodeData->generateUuid();

        $statement = (new MergeNodeCypherStatementBuilder($nodeData, true))->getCypherStatement();
        $cypher = $statement->getStatement(false);

        return $cypher ? $cypher . ";\n" : '';
    }


    protected function relationshipToCypher(RelationshipData $relationshipData): string
    {
        $relationshipData->generateUuid();

        $statement = (new MergeRelationshipCypherStatementBuilder($relationshipData))->getCypherStatement();
        $cypher = $statement->getStatement(false);

        return $cypher ? $cypher . ";\n" : '';
    }
}