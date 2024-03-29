<?php

namespace StrehleDe\TopicCards\Cypher;

use Twig\Environment;
use Twig\Loader\ArrayLoader;


class CypherStatement
{
    protected string $statement = '';
    protected array $parameters = [];


    /**
     * @return string
     */
    public function getStatement(bool $useParameters = true): string
    {
        return $this->renderStatement($useParameters);
    }


    /**
     * @return string
     */
    public function getUnrenderedStatement(): string
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


    public function append(string $statementFragment): self
    {
        $this->statement .= $statementFragment;
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
     * @param string $name
     * @param mixed $value
     * @return self
     */
    public function setParameter(string $name, $value): self
    {
        $this->parameters[$name] = $value;
        return $this;
    }


    /**
     * @param array $parameters
     * @return self
     */
    public function mergeParameters(array $parameters): self
    {
        $this->parameters = array_merge($this->parameters, $parameters);
        return $this;
    }


    protected function renderStatement(bool $useParameters = true): string
    {
        $twig = new Environment(
            new ArrayLoader(),
            ['cache' => false, 'autoescape' => false]
        );

        $template = $twig->createTemplate($this->statement);

        return $template->render($this->getTemplateVariables($useParameters));
    }


    protected function getTemplateVariables(bool $useParameters = true): array
    {
        $templateVariables = [];

        if ($useParameters) {
            foreach (array_keys($this->parameters) as $name) {
                $templateVariables[$name] = '$' . $name;
            }
        } else {
            foreach ($this->parameters as $name => $value) {
                $templateVariables[$name] = self::literalValue($value);
            }
        }

        return $templateVariables;
    }


    public static function literalValue($value): string
    {
        if (is_int($value) || is_float($value)) {
            return $value;
        }

        if (is_bool($value)) {
            return ($value ? 'true' : 'false');
        }

        return self::escapeString((string)$value);
    }


    /**
     * @see https://neo4j.com/docs/cypher-manual/current/syntax/expressions/#cypher-expressions-string-literals
     * @param string $value
     * @return string
     */
    public static function escapeString(string $value): string
    {
        $replacePairs = [
            '\\' => '\\\\',
            '"' => '\\"',
            "\n" => "\\n",
            "\r" => "\\r",
            "\t" => "\\t",
            "\b" => "\\b",
        ];

        return sprintf('"%s"', strtr($value, $replacePairs));
    }
}