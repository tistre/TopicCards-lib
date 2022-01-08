<?php

namespace TopicCards\Cypher;


class SetPropertiesCypherStatement implements CypherStatementInterface
{
    protected bool $isGenerated = false;
    protected array $parameters;
    protected string $variable;
    protected array $properties;
    protected bool $replaceAll = false;
    protected string $statement = '';


    public function __construct(string $variable, array $properties, bool $replaceAll)
    {
        $this->variable = $variable;
        $this->properties = $properties;
        $this->replaceAll = $replaceAll;
    }


    /**
     * @return string
     */
    public function getStatement(): string
    {
        $this->generateStatement();

        return $this->statement;
    }


    /**
     * @return array
     */
    public function getParameters(): array
    {
        $this->generateStatement();

        return $this->parameters;
    }


    protected function generateStatement(): void
    {
        if ($this->isGenerated) {
            return;
        }

        if (count($this->properties) === 0) {
            $this->isGenerated = true;
            return;
        }

        $propertiesStatement = new PropertiesCypherStatement($this->properties);

        $this->statement = sprintf(
            ' SET %s %s %s',
            $this->variable,
            ($this->replaceAll ? '=' : '+='),
            $propertiesStatement->getStatement()
        );

        $this->parameters = $propertiesStatement->getParameters();

        $this->isGenerated = true;
    }
}