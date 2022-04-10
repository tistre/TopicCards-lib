<?php

namespace StrehleDe\TopicCards\Cypher;

use Laudis\Neo4j\Databags\Statement;
use Twig\Environment;
use Twig\Loader\ArrayLoader;


class StatementTemplate
{
    protected array $labels;
    protected array $parameters;
    protected string $templateText;


    public function __construct(string $templateText, array $parameters, array $labels)
    {
        $this->templateText = $templateText;
        $this->parameters = $parameters;
        $this->labels = $labels;
    }


    /**
     * @return Statement
     */
    public function getStatement(): Statement
    {
        return new Statement($this->renderText(true), $this->parameters);
    }


    /**
     * @return string
     */
    public function getCypherText(): string
    {
        return $this->renderText(false);
    }


    /**
     * @return string
     */
    public function getTemplateText(): string
    {
        return $this->templateText;
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


    /**
     * @return array
     */
    public function getLabels(): array
    {
        return $this->labels;
    }


    /**
     * @param array $labels
     * @return self
     */
    public function setLabels(array $labels): self
    {
        $this->labels = $labels;
        return $this;
    }


    /**
     * @param string $name
     * @param string $value
     * @return self
     */
    public function addLabel(string $name, string $value): self
    {
        if (!isset($this->labels[$name])) {
            $this->labels[$name] = [];
        }

        $this->labels[$name][] = $value;

        return $this;
    }


    protected function renderText(bool $useParameters = true): string
    {
        $twig = new Environment(
            new ArrayLoader(),
            ['cache' => false, 'autoescape' => false]
        );

        $template = $twig->createTemplate($this->templateText);

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

        foreach ($this->labels as $name => $values) {
            $templateVariables[$name] = '';

            foreach ($values as $value) {
                // TODO: Escape ` as well?
                $templateVariables[$name] .= sprintf(':`%s`', self::escapeString($value));
            }
        }

        return $templateVariables;
    }


    public static function literalValue($value): string
    {
        if (is_array($value)) {
            $result = '[';
            $first = true;

            foreach ($value as $val) {
                if (!$first) {
                    $result .= ', ';
                }

                $first = false;

                $result .= self::literalValue($val);
            }

            $result .= ']';

            return $result;
        }

        if (is_null($value)) {
            return 'null';
        }

        if (is_int($value) || is_float($value)) {
            return $value;
        }

        if (is_bool($value)) {
            return ($value ? 'true' : 'false');
        }

        return sprintf('"%s"', self::escapeString((string)$value));
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

        return strtr($value, $replacePairs);
    }
}