<?php

namespace TopicCards\Cypher;

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
    public function setParameter(string $name, mixed $value): self
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
        $vars = $this->getTemplateVariables($useParameters);

        $res = $template->render($vars);

        return $res;
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
                $templateVariables[$name] = '"' . $value . '"';
            }
        }

        return $templateVariables;
    }
}