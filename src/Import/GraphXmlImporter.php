<?php

namespace TopicCards\Import;


use DOMElement;

class GraphXmlImporter
{
    /**
     * @param DOMElement $domNode
     * @return NodeImportData
     */
    public function getNodeData(DOMElement $domNode): NodeImportData
    {
        /*
        Example:

        <graph xmlns="https://topiccards.net/GraphCards/xmlns">
          <node>
            <id>21</id>
            <label>LABEL1</label>
            <label>LABEL2</label>
            <property>…</property>
          </node>
        </graph>
        */

        $nodeData = new NodeImportData();

        // ID

        foreach ($this->getChildrenByTagName($domNode, 'id') as $domSubNode) {
            $nodeData->id = trim($domSubNode->nodeValue);
        }

        // Labels

        foreach ($this->getChildrenByTagName($domNode,'label') as $domSubNode) {
            $label = trim($domSubNode->nodeValue);

            if (strlen($label) === 0) {
                continue;
            }

            $nodeData->labels[] = $label;
        }

        // Properties

        foreach ($this->getChildrenByTagName($domNode,'property') as $domSubNode) {
            $propertyData = $this->getPropertyData($domSubNode);

            if ((strlen($propertyData->name) === 0) || (count($propertyData->values) === 0)) {
                continue;
            }

            $nodeData->properties[] = $propertyData;
        }

        return $nodeData;
    }


    /**
     * @param DOMElement $domNode
     * @return RelationshipImportData
     */
    public function getRelationshipData(DOMElement $domNode): RelationshipImportData
    {
        /*
        Example:

        <graph xmlns="https://topiccards.net/GraphCards/xmlns">
          <relationship>
            <type>TYPE</type>
            <property>…</property>
            <start>
              <node>…</node>
            </start>
            <end>
              <node>…</node>
            </end>
          </relationship>
        </graph>
        */

        $relationshipData = new RelationshipImportData();

        // Type

        foreach ($this->getChildrenByTagName($domNode,'type') as $domSubNode) {
            $relationshipData->type = trim($domSubNode->nodeValue);
        }

        // Properties

        foreach ($this->getChildrenByTagName($domNode,'property') as $domSubNode) {
            $propertyData = $this->getPropertyData($domSubNode);

            if ((strlen($propertyData->name) === 0) || (count($propertyData->values) === 0)) {
                continue;
            }

            $relationshipData->properties[] = $propertyData;
        }

        // Start node

        foreach ($this->getChildrenByTagName($domNode, 'start') as $domSubNode) {
            foreach ($this->getChildrenByTagName($domSubNode,'node') as $nodeNode) {
                $relationshipData->startNode = $this->getNodeData($nodeNode);
            }
        }

        // End node

        foreach ($this->getChildrenByTagName($domNode,'end') as $domSubNode) {
            foreach ($this->getChildrenByTagName($domSubNode,'node') as $nodeNode) {
                $relationshipData->endNode = $this->getNodeData($nodeNode);
            }
        }

        return $relationshipData;
    }


    /**
     * @param DOMElement $domNode
     * @return PropertyImportData
     */
    protected function getPropertyData(DOMElement $domNode): PropertyImportData
    {
        /*
        Example:

        <property name="NAME1" type="string">
          <value>VALUE1</value>
          <value>VALUE2</value>
        </property>
        */

        $propertyData = new PropertyImportData();

        if ($domNode->hasAttribute('name')) {
            $propertyData->name = trim($domNode->getAttribute('name'));
        }

        if ($domNode->hasAttribute('type')) {
            $propertyData->type = trim($domNode->getAttribute('type'));
        }

        foreach ($this->getChildrenByTagName($domNode,'value') as $domSubNode) {
            $propertyData->values[] = trim($domSubNode->nodeValue);
        }

        return $propertyData;
    }


    /**
     * @param DOMElement $domNode
     * @param string $tagName
     * @return DOMElement[]
     */
    protected function getChildrenByTagName(DOMElement $domNode, string $tagName): array
    {
        $result = [];

        foreach ($domNode->childNodes as $childNode) {
            if (($childNode instanceof DOMElement) && ($childNode->tagName === $tagName)) {
                $result[] = $childNode;
            }
        }

        return $result;
    }
}