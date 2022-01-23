<?php

namespace StrehleDe\TopicCards\Cypher;

use Laudis\Neo4j\Types\Date;
use Laudis\Neo4j\Types\DateTime;
use Laudis\Neo4j\Types\Time;
use StrehleDe\TopicCards\Import\PropertyImportData;


class PropertiesCypherStatementBuilder implements CypherStatementBuilderInterface
{
    protected string $parameterPrefix;
    protected array $properties;


    public function __construct(array $properties, string $parameterPrefix = '')
    {
        $this->parameterPrefix = $parameterPrefix;
        $this->properties = $properties;
    }


    public function getCypherStatement(): CypherStatement
    {
        $cypherStatement = new CypherStatement();

        if (count($this->properties) === 0) {
            return $cypherStatement;
        }

        $first = true;

        foreach ($this->properties as $propertyData) {
            if ($first) {
                $cypherStatement->append('{');
                $first = false;
            } else {
                $cypherStatement->append(', ');
            }

            $this->addProperty($cypherStatement, $propertyData);
        }

        $cypherStatement->append('}');

        return $cypherStatement;
    }


    public function addProperty(CypherStatement $cypherStatement, PropertyImportData $propertyData): void
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

        $cypherStatement->append($fragment);
        $cypherStatement->setParameter($parameterName, $parameterValue);
    }


    protected function getFragment(PropertyImportData $propertyData, string $parameterName, &$parameterValue): string
    {
        if ($parameterValue instanceof DateTime) {
            $fragment = sprintf('%s: datetime({{ %s }})', $propertyData->name, $parameterName);
            $parameterValue = Converter::neo4jDateTimeToString($parameterValue);
        } elseif ($parameterValue instanceof Date) {
            $fragment = sprintf('%s: date({{ %s }})', $propertyData->name, $parameterName);
            $parameterValue = Converter::neo4jDateToString($parameterValue);
        } elseif ($parameterValue instanceof Time) {
            $fragment = sprintf('%s: time({{ %s }})', $propertyData->name, $parameterName);
            $parameterValue = Converter::neo4jTimeToString($parameterValue);
        } else {
            $fragment = sprintf('%s: {{ %s }}', $propertyData->name, $parameterName);
        }

        return $fragment;
    }
}