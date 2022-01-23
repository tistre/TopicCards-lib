<?php

namespace StrehleDe\TopicCards\Data;


class Data
{
    /** @var PropertyData[] */
    public array $properties = [];


    public function getProperty(string $name): ?PropertyData
    {
        foreach ($this->properties as $propertyData) {
            if ($propertyData->name !== $name) {
                continue;
            }

            return $propertyData;
        }

        return null;
    }
}