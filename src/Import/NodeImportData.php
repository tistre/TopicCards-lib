<?php

namespace TopicCards\Import;


class NodeImportData
{
    public string $id = '';
    public array $labels = [];

    /** @var PropertyImportData[] */
    public array $properties = [];
}