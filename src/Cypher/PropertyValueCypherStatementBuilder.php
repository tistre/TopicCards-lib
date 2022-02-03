<?php

namespace StrehleDe\TopicCards\Cypher;

use Laudis\Neo4j\Types\Date;
use Laudis\Neo4j\Types\DateTime;
use Laudis\Neo4j\Types\Time;
use StrehleDe\TopicCards\Data\PropertyData;


class PropertyValueCypherStatementBuilder implements CypherStatementBuilderInterface
{
    protected string $parameterPrefix;
    protected PropertyData $propertyData;


    public function __construct(PropertyData $propertyData, string $parameterPrefix = '')
    {
        $this->parameterPrefix = $parameterPrefix;
        $this->propertyData = $propertyData;
    }


    public function getCypherStatement(): CypherStatement
    {
        $cypherStatement = new CypherStatement();

        if (!$this->propertyData->hasAnyValue()) {
            return $cypherStatement;
        }

        $parameterName = $this->parameterPrefix . $this->propertyData->getName();

        if ($this->propertyData->hasValueList()) {
            $cypherStatement->append('[');

            foreach ($this->propertyData->getValueList() as $key => $parameterValue) {
                if ($key > 0) {
                    $cypherStatement->append(', ');
                }

                $parameterNameWithKey = $parameterName . '_' . $key;
                $cypherStatement->append($this->formatValue($parameterNameWithKey, $parameterValue));
                $cypherStatement->setParameter($parameterNameWithKey, $parameterValue);
            }

            $cypherStatement->append(']');
        } else {
            $parameterValue = $this->propertyData->getValue();
            $cypherStatement->append($this->formatValue($parameterName, $parameterValue));
            $cypherStatement->setParameter($parameterName, $parameterValue);
        }

        return $cypherStatement;
    }


    protected function formatValue(string $parameterName, &$parameterValue): string
    {
        if ($parameterValue instanceof DateTime) {
            $fragment = sprintf('datetime({{ %s }})', $parameterName);
            $parameterValue = Converter::neo4jDateTimeToString($parameterValue);
        } elseif ($parameterValue instanceof Date) {
            $fragment = sprintf('date({{ %s }})', $parameterName);
            $parameterValue = Converter::neo4jDateToString($parameterValue);
        } elseif ($parameterValue instanceof Time) {
            $fragment = sprintf('time({{ %s }})', $parameterName);
            $parameterValue = Converter::neo4jTimeToString($parameterValue);
        } else {
            $fragment = sprintf('{{ %s }}', $parameterName);
        }

        return $fragment;
    }
}