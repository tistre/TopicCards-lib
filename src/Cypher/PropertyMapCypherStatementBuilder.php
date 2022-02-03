<?php

namespace StrehleDe\TopicCards\Cypher;

use StrehleDe\TopicCards\Data\PropertyData;


class PropertyMapCypherStatementBuilder implements CypherStatementBuilderInterface
{
    protected string $parameterPrefix;
    /** @var PropertyData[] */
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

            $cypherStatement->append(sprintf('%s: ', $propertyData->getName()));

            $valueStatement = (new PropertyValueCypherStatementBuilder($propertyData, $this->parameterPrefix))
                ->getCypherStatement();

            $cypherStatement
                ->append($valueStatement->getUnrenderedStatement())
                ->mergeParameters($valueStatement->getParameters());
        }

        $cypherStatement->append('}');

        return $cypherStatement;
    }
}