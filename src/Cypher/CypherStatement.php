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
    public function getStatement(): string
    {
        return $this->renderStatement();
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


    protected function renderStatement(): string
    {
        $twig = new Environment(new ArrayLoader());
        $template = $twig->createTemplate($this->statement);

        $templateVariables = [];

        foreach (array_keys($this->parameters) as $name) {
            $templateVariables[$name] = '$' . $name;
        }

        return $twig->render($template, $templateVariables);
    }
}