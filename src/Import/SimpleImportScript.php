<?php

namespace TopicCards\Import;

use Laudis\Neo4j\Contracts\ClientInterface;
use Laudis\Neo4j\Contracts\TransactionInterface;
use Ramsey\Uuid\Uuid;
use TopicCards\Cypher\CypherStatement;


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

        // TODO Move labels to SET statements so that a change in labels does not lead to duplicate nodes
        // TODO Remove outdated labels and properties (optionally)

        $statement = new CypherStatement(
            sprintf(
                'MERGE (n%s {uuid: $uuid})',
                CypherStatement::labelsString($nodeData->labels)
            ),
            ['uuid' => $uuid]
        );

        $statement->addProperties('n.', $nodeData->properties);

        $statement->append(' RETURN n.uuid');

        print_r($statement);

        $this->client->writeTransaction(static function (TransactionInterface $tsx) use ($statement) {
            $tsx->run($statement->getStatement(), $statement->getParameters());
        });

        printf("Imported node <%s>\n", $uuid);
    }


    protected function importRelationship(RelationshipImportData $relationshipData): void
    {
        $uuid = $this->getUuid($relationshipData);

        // TODO Move type to SET statements so that a change in type does not lead to duplicate relationships
        // TODO Remove outdated properties (optionally)

        $statement = new CypherStatement(
            sprintf(
                'MATCH (startNode {uuid: $startUuid}) MATCH (endNode {uuid: $endUuid})' .
                ' MERGE (startNode)-[r%s {uuid: $uuid}]->(endNode)',
                CypherStatement::labelsString([$relationshipData->type])
            ),
            [
                'startUuid' => $relationshipData->startNode->getProperty('uuid')->values[0] ?? '',
                'endUuid' => $relationshipData->endNode->getProperty('uuid')->values[0] ?? ''
            ]
        );

        $statement->addProperties('r.', $relationshipData->properties);

        $statement->append(' RETURN r.uuid');

        print_r($statement);

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