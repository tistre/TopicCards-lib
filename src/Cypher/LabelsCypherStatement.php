<?php

namespace TopicCards\Cypher;


class LabelsCypherStatement implements CypherStatementInterface
{
    /** @var string[] */
    protected array $labels;


    public function __construct(array $labels)
    {
        $this->labels = $labels;
    }


    public function getStatement(): string
    {
        $statement = '';

        foreach ($this->labels as $label) {
            if (strlen($label) === 0) {
                continue;
            }

            $statement .= sprintf(':`%s`', $label);
        }

        return $statement;
    }


    public function getParameters(): array
    {
        return [];
    }
}