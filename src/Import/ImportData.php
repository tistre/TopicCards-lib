<?php

namespace StrehleDe\TopicCards\Import;


class ImportData
{
    /** @var PropertyImportData[] */
    public array $properties = [];


    public function getProperty(string $name): ?PropertyImportData
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