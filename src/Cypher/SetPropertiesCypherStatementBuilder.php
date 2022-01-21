<?php

namespace TopicCards\Cypher;


class SetPropertiesCypherStatementBuilder implements CypherStatementBuilderInterface
{
    protected string $variable;
    protected array $properties;
    protected bool $replaceAll = false;


    public function __construct(string $variable, array $properties, bool $replaceAll)
    {
        $this->variable = $variable;
        $this->properties = $properties;
        $this->replaceAll = $replaceAll;
    }


    public function getCypherStatement(): CypherStatement
    {
        $cypherStatement = new CypherStatement();

        if (count($this->properties) === 0) {
            return $cypherStatement;
        }

        $propertiesStatement = (new PropertiesCypherStatementBuilder($this->properties))->getCypherStatement();

        $cypherStatement->setParameters($propertiesStatement->getParameters());

        $cypherStatement->setStatement(sprintf(
            ' SET %s %s %s',
            $this->variable,
            ($this->replaceAll ? '=' : '+='),
            $propertiesStatement->getStatement()
        ));

        return $cypherStatement;
    }
}