<?php

namespace StrehleDe\TopicCards\Cypher;

use StrehleDe\TopicCards\Data\PropertyData;


class WherePropertiesCypherStatementBuilder implements CypherStatementBuilderInterface
{
    protected string $parameterPrefix;
    /** @var PropertyData[] */
    protected array $properties;
    protected string $variable;


    public function __construct(string $variable, array $properties, string $parameterPrefix = '')
    {
        $this->parameterPrefix = $parameterPrefix;
        $this->properties = $properties;
        $this->variable = $variable;
    }


    public function getCypherStatement(): CypherStatement
    {
        $cypherStatement = new CypherStatement();

        if (count($this->properties) === 0) {
            return $cypherStatement;
        }

        $cypherStatement->append('WHERE ');

        $first = true;

        foreach ($this->properties as $propertyData) {
            if ($first) {
                $first = false;
            } else {
                $cypherStatement->append(', ');
            }

            $comparisonOperator = PropertyClauseCypherStatementBuilder::OPERATOR_EQUALS;

            // If value list, switch operator from 'property = value' to 'value IN property'
            // TODO This is an assumption, make it configurable somehow?

            if ($propertyData->hasValueList()) {
                $comparisonOperator = PropertyClauseCypherStatementBuilder::OPERATOR_IN_REVERSE;
            }

            $propertyStatement = (new PropertyClauseCypherStatementBuilder(
                $this->variable,
                $propertyData,
                $comparisonOperator,
                $this->parameterPrefix)
            )
                ->getCypherStatement();

            $cypherStatement
                ->append($propertyStatement->getUnrenderedStatement())
                ->mergeParameters($propertyStatement->getParameters());
        }

        return $cypherStatement;
    }
}