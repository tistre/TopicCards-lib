<?php

namespace TopicCards\Import;


class RelationshipImportData extends ImportData
{
    public string $type = '';
    public NodeImportData $startNode;
    public NodeImportData $endNode;
}