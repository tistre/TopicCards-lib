<?php

namespace TopicCards\Cypher;


class LabelsCypherStatementBuilder implements CypherStatementBuilderInterface
{
    /** @var string[] */
    protected array $labels;


    public function __construct(array $labels)
    {
        $this->labels = $labels;
    }


    public function getCypherStatement(): CypherStatement
    {
        $cypherStatement = new CypherStatement();

        foreach ($this->labels as $label) {
            if (strlen($label) === 0) {
                continue;
            }

            $cypherStatement->append(sprintf(':`%s`', $label));
        }

        return $cypherStatement;
    }
}