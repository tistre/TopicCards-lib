<?php

namespace StrehleDe\TopicCards\Import;


class GraphXmlParameter
{
    const TYPE_BOOLEAN = 'boolean';
    const TYPE_FLOAT = 'float';
    const TYPE_INTEGER = 'integer';
    const TYPE_NULL = 'null';
    const TYPE_STRING = 'string';

    protected string $name;
    protected string $type;
    protected $strValue;


    /**
     * @param string $name
     * @param string|string[] $strValue
     * @param string $type
     */
    public function __construct(string $name, $strValue, string $type = self::TYPE_STRING)
    {
        $this->name = $name;
        $this->type = $type;
        $this->strValue = $strValue;
    }


    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }


    /**
     * @return bool|float|int|null|string|array
     */
    public function getValue()
    {
        if (!is_array($this->strValue)) {
            return self::stringToTypedValue($this->strValue, $this->type);
        }

        $value = [];

        foreach ($this->strValue as $strVal) {
            $value[] = self::stringToTypedValue($strVal, $this->type);
        }

        return $value;
    }


    /**
     * @param string $value
     * @param string $type
     * @return bool|float|int|null|string
     */
    protected static function stringToTypedValue(string $value, string $type)
    {
        $value = trim($value);

        // Names of types taken from https://github.com/neo4j-php/neo4j-php-client#accessing-the-results

        switch (strtolower($type)) {
            case self::TYPE_BOOLEAN:
                $value = boolval($value);
                break;
            case self::TYPE_FLOAT:
                $value = floatval($value);
                break;
            case self::TYPE_INTEGER:
                $value = intval($value);
                break;
            case self::TYPE_NULL:
                $value = null;
                break;
            default:
                $value = (string)$value;
                break;
        }

        return $value;
    }
}