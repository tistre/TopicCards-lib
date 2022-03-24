<?php

namespace StrehleDe\TopicCards\Import;

use Laudis\Neo4j\Contracts\ClientInterface;
use StrehleDe\TopicCards\Cypher\StatementTemplate;


class SimpleImportScript
{
    protected ClientInterface $neo4jClient;


    public function __construct(ClientInterface $neo4jClient)
    {
        $this->neo4jClient = $neo4jClient;
    }


    public function importFile(string $filename): void
    {
        $statementTemplates = new GraphXmlReader($filename, [GraphXmlImporter::class, 'getStatementTemplate']);

        /** @var StatementTemplate $statementTemplate */
        foreach ($statementTemplates as $statementTemplate) {
            $this->neo4jClient->runStatement($statementTemplate->getStatement());
        }
    }


    public function convertFileToCypher(string $filename): void
    {
        $statementTemplates = new GraphXmlReader($filename, [GraphXmlImporter::class, 'getStatementTemplate']);

        /** @var StatementTemplate $statementTemplate */
        foreach ($statementTemplates as $statementTemplate) {
            echo $statementTemplate->getCypherText() . ";\n";
        }
    }
}