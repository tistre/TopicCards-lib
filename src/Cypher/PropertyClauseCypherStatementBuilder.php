<?php

namespace StrehleDe\TopicCards\Cypher;

use StrehleDe\TopicCards\Data\PropertyData;


class PropertyClauseCypherStatementBuilder implements CypherStatementBuilderInterface
{
    const OPERATOR_EQUALS = '=';
    const OPERATOR_IN = 'IN';
    const OPERATOR_IN_REVERSE = 'IN_REVERSE';

    protected string $comparisonOperator;
    protected string $parameterPrefix;
    protected PropertyData $propertyData;
    protected string $variable;


    public function __construct(
        string $variable,
        PropertyData $propertyData,
        string $comparisonOperator = self::OPERATOR_EQUALS,
        string $parameterPrefix = ''
    ) {
        $this->comparisonOperator = $comparisonOperator;
        $this->parameterPrefix = $parameterPrefix;
        $this->propertyData = $propertyData;
        $this->variable = $variable;
    }


    public function getCypherStatement(): CypherStatement
    {
        $cypherStatement = new CypherStatement();

        if (($this->propertyData->getName() === '') || (!$this->propertyData->hasAnyValue())) {
            return $cypherStatement;
        }

        if ($this->comparisonOperator === self::OPERATOR_IN_REVERSE) {
            // Weird IN_REVERSE logic: We want to search 'value in listProperty',
            // so we convert value to scalar if list (and do so in a copy to not mess with the original object)

            $propertyData = clone $this->propertyData;

            if ($propertyData->hasValueList()) {
                $propertyData->setValue($propertyData->getValueList()[0]);
            }

            $valueStatement = (new PropertyValueCypherStatementBuilder($propertyData, $this->parameterPrefix))
                ->getCypherStatement();

            $stmt = sprintf(
                '%s IN %s.%s',
                $valueStatement->getUnrenderedStatement(),
                $this->variable,
                $this->propertyData->getName()
            );
        } else {
            $valueStatement = (new PropertyValueCypherStatementBuilder($this->propertyData, $this->parameterPrefix))
                ->getCypherStatement();

            $stmt = sprintf(
                '%s.%s %s %s',
                $this->variable,
                $this->propertyData->getName(),
                $this->comparisonOperator,
                $valueStatement->getUnrenderedStatement()
            );
        }

        $cypherStatement
            ->setStatement($stmt)
            ->setParameters($valueStatement->getParameters());

        return $cypherStatement;
    }
}