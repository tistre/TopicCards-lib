<?php

namespace StrehleDe\TopicCards\Data;


class RelationshipData extends Data
{
    protected string $type = '';
    protected ?NodeData $startNode = null;
    protected ?NodeData $endNode = null;


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
     * @return NodeData
     */
    public function getStartNode(): ?NodeData
    {
        return $this->startNode;
    }


    /**
     * @param NodeData $startNode
     * @return self
     */
    public function setStartNode(NodeData $startNode): self
    {
        $this->startNode = $startNode;
        return $this;
    }


    /**
     * @return NodeData
     */
    public function getEndNode(): ?NodeData
    {
        return $this->endNode;
    }


    /**
     * @param NodeData $endNode
     * @return self
     */
    public function setEndNode(NodeData $endNode): self
    {
        $this->endNode = $endNode;
        return $this;
    }
}