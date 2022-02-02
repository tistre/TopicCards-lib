<?php

namespace StrehleDe\TopicCards\Cypher;

use Laudis\Neo4j\Types\Date;
use Laudis\Neo4j\Types\DateTime;
use Laudis\Neo4j\Types\Time;
use StrehleDe\TopicCards\Data\PropertyData;


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


    public function addProperty(CypherStatement $cypherStatement, PropertyData $propertyData): void
    {
        if (!$propertyData->hasAnyValue()) {
            return;
        }

        $fragment = sprintf('%s: ', $propertyData->getName());
        $parameterName = $this->parameterPrefix . $propertyData->getName();

        if ($propertyData->hasValueList()) {
            $fragment .= '[';

            foreach ($propertyData->getValueList() as $key => $parameterValue) {
                if ($key > 0) {
                    $fragment .= ', ';
                }

                $parameterNameWithKey = $parameterName . '_' . $key;
                $fragment .= $this->getFragment($parameterNameWithKey, $parameterValue);
                $cypherStatement->setParameter($parameterNameWithKey, $parameterValue);
            }

            $fragment .= ']';
        } else {
            $parameterValue = $propertyData->getValue();
            $fragment .= $this->getFragment($parameterName, $parameterValue);
            $cypherStatement->setParameter($parameterName, $parameterValue);
        }

        $cypherStatement->append($fragment);
    }


    protected function getFragment(string $parameterName, &$parameterValue): string
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