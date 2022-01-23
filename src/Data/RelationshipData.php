<?php

namespace StrehleDe\TopicCards\Data;


class RelationshipData extends Data
{
    public string $type = '';
    public NodeData $startNode;
    public NodeData $endNode;
}