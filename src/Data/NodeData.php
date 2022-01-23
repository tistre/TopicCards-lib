<?php

namespace StrehleDe\TopicCards\Data;


class NodeData extends Data
{
    protected string $id = '';
    protected array $labels = [];


    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }


    /**
     * @param string $id
     * @return self
     */
    public function setId(string $id): self
    {
        $this->id = $id;
        return $this;
    }


    /**
     * @return string[]
     */
    public function getLabels(): array
    {
        return $this->labels;
    }


    /**
     * @param string[] $labels
     * @return self
     */
    public function setLabels(array $labels): self
    {
        $this->labels = $labels;
        return $this;
    }


    /**
     * @param string $label
     * @return $this
     */
    public function addLabel(string $label): self
    {
        $this->labels[] = $label;
        return $this;
    }
}