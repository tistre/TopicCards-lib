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
        $statementTemplates = $this->getReader($filename);

        /** @var StatementTemplate|bool $statementTemplate */
        foreach ($statementTemplates as $statementTemplate) {
            if (!$statementTemplate) {
                continue;
            }

            $this->neo4jClient->runStatement($statementTemplate->getStatement());
        }
    }


    public function convertFileToCypher(string $filename): void
    {
        $statementTemplates = $this->getReader($filename);

        /** @var StatementTemplate|bool $statementTemplate */
        foreach ($statementTemplates as $statementTemplate) {
            if (!$statementTemplate) {
                continue;
            }

            echo $statementTemplate->getCypherText() . ";\n";
        }
    }


    protected function getReader(string $filename): GraphXmlReader
    {
        $graphXmlImporter = new GraphXmlImporter();

        $reader = new GraphXmlReader(
            $filename,
            [$graphXmlImporter, 'setDefault'],
            [$graphXmlImporter, 'getStatementTemplate']
        );

        return $reader;
    }
}