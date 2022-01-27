<?php

namespace StrehleDe\TopicCards\Data;

use Ramsey\Uuid\Uuid;


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
        $index = $this->getPropertyIndex($propertyData->getName());

        if ($index < 0) {
            $index = count($this->properties);
        }

        $this->properties[$index] = $propertyData;

        return $this;
    }


    public function getProperty(string $name): ?PropertyData
    {
        $index = $this->getPropertyIndex($name);

        if ($index < 0) {
            return null;
        }

        return $this->properties[$index];
    }


    /**
     * @param string $name
     * @return mixed|null
     */
    public function getPropertyValue(string $name)
    {
        $property = $this->getProperty($name);

        if (is_null($property)) {
            return null;
        }

        return $property->getValue();
    }


    public function hasProperty(string $name): bool
    {
        return ($this->getPropertyIndex($name) >= 0);
    }


    protected function getPropertyIndex(string $name): int
    {
        foreach ($this->properties as $index => $propertyData) {
            if ($propertyData->getName() !== $name) {
                continue;
            }

            return $index;
        }

        return -1;
    }


    public function generateUuid(): string
    {
        // If a uuid property already exists, return its value

        $existingUuid = $this->getPropertyValue('uuid');

        if (!is_null($existingUuid)) {
            return $existingUuid;
        }

        // Otherwise create a new uuid property

        $newUuid = Uuid::uuid4();

        $propertyData = (new PropertyData())
            ->setName('uuid')
            ->setValue((string)$newUuid);

        $this->addProperty($propertyData);

        return $newUuid;
    }
}