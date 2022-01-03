<?php

namespace TopicCards\Cypher;

use TopicCards\Import\PropertyImportData;


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

        $first = true;

        foreach ($this->properties as $propertyData) {
            if ($first) {
                $this->statement .= sprintf(
                    ' SET %s %s {',
                    $this->variable,
                    ($this->replaceAll ? '=' : '+=')
                );

                $first = false;
            } else {
                $this->statement .= ', ';
            }

            $this->addProperty($propertyData);
        }

        $this->statement .= '}';

        $this->isGenerated = true;
    }


    public function addProperty(PropertyImportData $propertyData): void
    {
        if (count($propertyData->values) === 0) {
            return;
        }

        $parameterValue = $propertyData->values;

        // To Cypher, a single-element array is different from a scalar value
        if (count($parameterValue) === 1) {
            $parameterValue = array_pop($parameterValue);
        }

        $fragment = $this->getFragment($propertyData, $parameterValue);
        $this->statement .= $fragment;
        $this->parameters[$propertyData->name] = $parameterValue;
    }


    protected function getFragment(PropertyImportData $propertyData, &$parameterValue): string
    {
        if ($parameterValue instanceof \Laudis\Neo4j\Types\DateTime) {
            $fragment = sprintf('%s: datetime($%s)', $propertyData->name, $propertyData->name);
            $parameterValue = Converter::neo4jDateTimeToString($parameterValue);
        } elseif ($parameterValue instanceof \Laudis\Neo4j\Types\Date) {
            $fragment = sprintf('%s: date($%s)', $propertyData->name, $propertyData->name);
            $parameterValue = Converter::neo4jDateToString($parameterValue);
        } elseif ($parameterValue instanceof \Laudis\Neo4j\Types\Time) {
            $fragment = sprintf('%s: time($%s)', $propertyData->name, $propertyData->name);
            $parameterValue = Converter::neo4jTimeToString($parameterValue);
        } else {
            $fragment = sprintf('%s: $%s', $propertyData->name, $propertyData->name);
        }

        return $fragment;
    }
}