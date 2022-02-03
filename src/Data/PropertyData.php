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
    protected $value = null;
    protected array $valueList = [];


    /**
     * @param string $name
     * @param mixed $value
     */
    public function __construct(string $name = '', $value = null)
    {
        if ($name !== '') {
            $this->setName($name);
        }

        if (!is_null($value)) {
            if (is_array($value)) {
                $this->setValueList($value);
            } else {
                $this->setValue($value);
            }
        }
    }


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
     * @return mixed|null
     */
    public function getValue()
    {
        return $this->value;
    }


    /**
     * @param mixed $value
     * @return self
     */
    public function setValue($value): self
    {
        $this->value = $value;
        $this->valueList = [];
        return $this;
    }


    /**
     * @return bool
     */
    public function hasValue(): bool
    {
        return (!is_null($this->value));
    }


    /**
     * @return array
     */
    public function getValueList(): array
    {
        return $this->valueList;
    }


    /**
     * @param array $valueList
     * @return self
     */
    public function setValueList(array $valueList): self
    {
        $this->valueList = $valueList;
        $this->value = null;
        return $this;
    }


    public function hasValueList(): bool
    {
        return (count($this->valueList) > 0);
    }


    /**
     * @return bool
     */
    public function hasAnyValue(): bool
    {
        return ($this->hasValue() || $this->hasValueList());
    }
}