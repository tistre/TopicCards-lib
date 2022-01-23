<?php

namespace StrehleDe\TopicCards\Data;


class Data
{
    /** @var PropertyData[] */
    protected array $properties = [];


    /**
     * @return PropertyData[]
     */
    public function getProperties(): array
    {
        return $this->properties;
    }


    /**
     * @param PropertyData[] $properties
     * @return self
     */
    public function setProperties(array $properties): self
    {
        $this->properties = $properties;
        return $this;
    }


    /**
     * @param PropertyData $propertyData
     * @return self
     */
    public function addProperty(PropertyData $propertyData): self
    {
        $this->properties[] = $propertyData;
        return $this;
    }


    public function getProperty(string $name): ?PropertyData
    {
        foreach ($this->properties as $propertyData) {
            if ($propertyData->getName() !== $name) {
                continue;
            }

            return $propertyData;
        }

        return null;
    }
}