<?php

namespace TopicCards\Cypher;

use Laudis\Neo4j\Types\Date;
use Laudis\Neo4j\Types\DateTime;
use Laudis\Neo4j\Types\Time;
use TopicCards\Import\PropertyImportData;


class PropertiesCypherStatement implements CypherStatementInterface
{
    protected bool $isGenerated = false;
    protected array $parameters;
    protected string $parameterPrefix;
    protected array $properties;
    protected string $statement = '';


    public function __construct(array $properties, string $parameterPrefix = '')
    {
        $this->parameterPrefix = $parameterPrefix;
        $this->properties = $properties;
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
                $this->statement .= '{';
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

        $parameterName = $this->parameterPrefix . $propertyData->name;
        $parameterValue = $propertyData->values;

        // To Cypher, a single-element array is different from a scalar value
        if (count($parameterValue) === 1) {
            $parameterValue = array_pop($parameterValue);
        }

        $fragment = $this->getFragment($propertyData, $parameterName, $parameterValue);
        $this->statement .= $fragment;
        $this->parameters[$parameterName] = $parameterValue;
    }


    protected function getFragment(PropertyImportData $propertyData, string $parameterName, &$parameterValue): string
    {
        if ($parameterValue instanceof DateTime) {
            $fragment = sprintf('%s: datetime($%s)', $propertyData->name, $parameterName);
            $parameterValue = Converter::neo4jDateTimeToString($parameterValue);
        } elseif ($parameterValue instanceof Date) {
            $fragment = sprintf('%s: date($%s)', $propertyData->name, $parameterName);
            $parameterValue = Converter::neo4jDateToString($parameterValue);
        } elseif ($parameterValue instanceof Time) {
            $fragment = sprintf('%s: time($%s)', $propertyData->name, $parameterName);
            $parameterValue = Converter::neo4jTimeToString($parameterValue);
        } else {
            $fragment = sprintf('%s: $%s', $propertyData->name, $parameterName);
        }

        return $fragment;
    }
}