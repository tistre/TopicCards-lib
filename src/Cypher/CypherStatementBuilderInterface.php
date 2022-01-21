<?php

namespace TopicCards\Cypher;


interface CypherStatementBuilderInterface
{
    public function getCypherStatement(): CypherStatement;
}