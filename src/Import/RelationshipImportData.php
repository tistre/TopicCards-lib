<?php

namespace TopicCards\Import;


class RelationshipImportData
{
    public string $type = '';
    public NodeImportData $startNode;
    public NodeImportData $endNode;

    /** @var PropertyImportData[] */
    public array $properties = [];
}