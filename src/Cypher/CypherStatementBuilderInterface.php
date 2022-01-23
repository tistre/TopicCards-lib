<?php

namespace StrehleDe\TopicCards\Cypher;


interface CypherStatementBuilderInterface
{
    public function getCypherStatement(): CypherStatement;
}