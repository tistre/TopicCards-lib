<?php

namespace StrehleDe\TopicCards\Import;

use Laudis\Neo4j\Contracts\ClientInterface;
use Laudis\Neo4j\Contracts\TransactionInterface;
use StrehleDe\TopicCards\Cypher\CreateNodeCypherStatementBuilder;
use StrehleDe\TopicCards\Cypher\MergeNodeCypherStatementBuilder;
use StrehleDe\TopicCards\Cypher\MergeRelationshipCypherStatementBuilder;
use StrehleDe\TopicCards\Data\NodeData;
use StrehleDe\TopicCards\Data\RelationshipData;


class SimpleImportScript
{
    protected ClientInterface $neo4jClient;


    public function __construct(ClientInterface $neo4jClient)
    {
        $this->neo4jClient = $neo4jClient;
    }


    public function importFile(string $filename): void
    {
        $dataObjects = new GraphXmlReader($filename);

        foreach ($dataObjects as $importData) {
            if ($importData instanceof NodeData) {
                $this->importNode($importData);
            } elseif ($importData instanceof RelationshipData) {
                $this->importRelationship($importData);
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


    protected function importNode(NodeData $nodeData): void
    {
        $uuid = $nodeData->generateUuid();

        if (is_null($nodeData->getMergeData())) {
            $statement = (new CreateNodeCypherStatementBuilder($nodeData))->getCypherStatement();
        } else {
            $statement = (new MergeNodeCypherStatementBuilder($nodeData, true))->getCypherStatement();
        }

        print_r($statement->getStatement(false)); echo "\n";

        $this->neo4jClient->writeTransaction(static function (TransactionInterface $tsx) use ($statement) {
            $tsx->run($statement->getStatement(), $statement->getParameters());
        });

        printf("Imported node <%s>\n", $uuid);
    }


    protected function importRelationship(RelationshipData $relationshipData): void
    {
        $uuid = $relationshipData->generateUuid();

        $statement = (new MergeRelationshipCypherStatementBuilder($relationshipData))->getCypherStatement();
        print_r($statement->getStatement(false)); echo "\n";

        $this->neo4jClient->writeTransaction(static function (TransactionInterface $tsx) use ($statement) {
            $tsx->run($statement->getStatement(), $statement->getParameters());
        });

        printf("Imported relationship <%s>\n", $uuid);
    }


    protected function nodeToCypher(NodeData $nodeData): string
    {
        if (is_null($nodeData->getMergeData())) {
            $statement = (new CreateNodeCypherStatementBuilder($nodeData))->getCypherStatement();
        } else {
            $statement = (new MergeNodeCypherStatementBuilder($nodeData, true))->getCypherStatement();
        }

        $cypher = $statement->getStatement(false);

        return $cypher ? $cypher . ";\n" : '';
    }


    protected function relationshipToCypher(RelationshipData $relationshipData): string
    {
        $statement = (new MergeRelationshipCypherStatementBuilder($relationshipData))->getCypherStatement();
        $cypher = $statement->getStatement(false);

        return $cypher ? $cypher . ";\n" : '';
    }
}