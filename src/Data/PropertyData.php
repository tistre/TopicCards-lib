<?php

namespace StrehleDe\TopicCards\Data;


class PropertyData
{
    const TYPE_INTEGER = 'integer';
    const TYPE_FLOAT = 'float';
    const TYPE_BOOLEAN = 'boolean';
    const TYPE_DATE = 'date';
    const TYPE_TIME = 'time';
    const TYPE_DATETIME = 'datetime';

    protected string $name = '';
    protected string $type = '';
    protected array $values = [];


    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }


    /**
     * @param string $name
     * @return self
     */
    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }


    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }


    /**
     * @param string $type
     * @return self
     */
    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }


    /**
     * @return array
     */
    public function getValues(): array
    {
        return $this->values;
    }


    /**
     * @return mixed|null
     */
    public function getValue()
    {
        if (empty($this->values)) {
            return null;
        }

        return $this->values[0];
    }


    /**
     * @param array $values
     * @return self
     */
    public function setValues(array $values): self
    {
        $this->values = $values;
        return $this;
    }


    /**
     * @param mixed $value
     * @return self
     */
    public function setValue($value): self
    {
        return $this->setValues([$value]);
    }


    /**
     * @param mixed $value
     * @return self
     */
    public function addValue($value): self
    {
        $this->values[] = $value;
        return $this;
    }
}