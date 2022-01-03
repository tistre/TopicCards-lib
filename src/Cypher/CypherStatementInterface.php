<?php

namespace TopicCards\Cypher;


interface CypherStatementInterface
{
    public function getStatement(): string;

    public function getParameters(): array;
}