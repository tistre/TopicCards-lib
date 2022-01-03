<?php

namespace TopicCards\Cypher;

use DateTimeImmutable;
use TopicCards\Import\PropertyImportData;


class CypherStatement
{
    protected string $statement;
    protected array $parameters;


    public static function labelsString(array $labels): string
    {
        $result = '';

        foreach ($labels as $label) {
            if (strlen($label) === 0) {
                continue;
            }

            $result .= sprintf(':`%s`', $label);
        }

        return $result;
    }


    /**
     * CypherStatement constructor.
     * @param string $statement
     * @param array $parameters
     */
    public function __construct(string $statement, array $parameters = [])
    {
        $this->setStatement($statement);
        $this->setParameters($parameters);
    }


    /**
     * @return string
     */
    public function getStatement(): string
    {
        return $this->statement;
    }


    /**
     * @param string $statement
     * @return self
     */
    public function setStatement(string $statement): self
    {
        $this->statement = $statement;
        return $this;
    }


    /**
     * @param string $fragment
     * @return $this
     */
    public function append(string $fragment): self
    {
        $this->statement .= $fragment;
        return $this;
    }


    /**
     * @return array
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }


    /**
     * @param array $parameters
     * @return self
     */
    public function setParameters(array $parameters): self
    {
        $this->parameters = $parameters;
        return $this;
    }


    /**
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function setParameter(string $key, $value): self
    {
        $this->parameters[$key] = $value;
        return $this;
    }


    public function addProperty(string $prefix, PropertyImportData $propertyData): void
    {
        if (count($propertyData->values) === 0) {
            return;
        }

        $parameterValue = $propertyData->values;

        // To Cypher, a single-element array is different from a scalar value
        if (count($parameterValue) === 1) {
            $parameterValue = array_pop($parameterValue);
        }

        $fragment = $this->getFragment($prefix, $propertyData, $parameterValue);
        $this->append($fragment);
        $this->setParameter($propertyData->name, $parameterValue);
    }


    protected function getFragment(string $prefix, PropertyImportData $propertyData, &$parameterValue): string
    {
        if ($parameterValue instanceof \Laudis\Neo4j\Types\DateTime) {
            $fragment = sprintf('%s%s = datetime($%s)', $prefix, $propertyData->name, $propertyData->name);
            $parameterValue = $parameterValue->toDateTime()->format('c');
        } elseif ($parameterValue instanceof \Laudis\Neo4j\Types\Date) {
            $fragment = sprintf('%s%s = date($%s)', $prefix, $propertyData->name, $propertyData->name);
            $parameterValue = $parameterValue->toDateTime()->format('Y-m-d');
        } elseif ($parameterValue instanceof \Laudis\Neo4j\Types\Time) {
            $fragment = sprintf('%s%s = time($%s)', $prefix, $propertyData->name, $propertyData->name);
            $parameterValue = (new DateTimeImmutable('@' . $parameterValue->getSeconds()))->format('H:i:s');
        } else {
            $fragment = sprintf('%s%s = $%s', $prefix, $propertyData->name, $propertyData->name);
        }

        return $fragment;
    }


    /**
     * @param string $prefix
     * @param PropertyImportData[] $properties
     */
    public function addProperties(string $prefix, array $properties): void
    {
        if (count($properties) === 0) {
            return;
        }

        $first = true;

        foreach ($properties as $propertyData) {
            if ($first) {
                $this->append(' SET ');
                $first = false;
            } else {
                $this->append(', ');
            }

            $this->addProperty($prefix, $propertyData);
        }
    }
}