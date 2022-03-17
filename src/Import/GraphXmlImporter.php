<?php

namespace StrehleDe\TopicCards\Import;

use DOMElement;
use StrehleDe\TopicCards\Cypher\Converter;
use StrehleDe\TopicCards\Data\NodeData;
use StrehleDe\TopicCards\Data\PropertyData;
use StrehleDe\TopicCards\Data\RelationshipData;


class GraphXmlImporter
{
    /**
     * @param DOMElement $domNode
     * @return NodeData
     */
    public function getNodeData(DOMElement $domNode): NodeData
    {
        /*
        Example:

        <graph xmlns="https://topiccards.net/GraphCards/xmlns">
          <node>
            <merge>
              <label>LABEL1</label>
              <property>…</property>
            </merge>
            <id>21</id>
            <label>LABEL2</label>
            <property>…</property>
          </node>
        </graph>
        */

        $nodeData = new NodeData();

        // Merge

        foreach ($this->getChildrenByTagName($domNode, 'merge') as $domSubNode) {
            $mergeData = $this->getNodeData($domSubNode);

            if ((count($mergeData->getLabels()) === 0) && (count($mergeData->getProperties()) === 0)) {
                continue;
            }

            $nodeData->setMergeData($mergeData);

            // Only one <merge> node is supported
            break;
        }

        // ID

        foreach ($this->getChildrenByTagName($domNode, 'id') as $domSubNode) {
            $nodeData->setId(trim($domSubNode->nodeValue));
        }

        // Labels

        foreach ($this->getChildrenByTagName($domNode, 'label') as $domSubNode) {
            $label = trim($domSubNode->nodeValue);

            if (strlen($label) === 0) {
                continue;
            }

            $nodeData->addLabel($label);
        }

        // Properties

        foreach ($this->getChildrenByTagName($domNode, 'property') as $domSubNode) {
            $propertyData = $this->getPropertyData($domSubNode);

            if ((strlen($propertyData->getName()) === 0) || (!$propertyData->hasAnyValue())) {
                continue;
            }

            $nodeData->addProperty($propertyData);
        }

        return $nodeData;
    }


    /**
     * @param DOMElement $domNode
     * @return RelationshipData
     */
    public function getRelationshipData(DOMElement $domNode): RelationshipData
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

        $relationshipData = new RelationshipData();

        // Type

        foreach ($this->getChildrenByTagName($domNode, 'type') as $domSubNode) {
            $relationshipData->setType(trim($domSubNode->nodeValue));
        }

        // Properties

        foreach ($this->getChildrenByTagName($domNode, 'property') as $domSubNode) {
            $propertyData = $this->getPropertyData($domSubNode);

            if ((strlen($propertyData->getName()) === 0) || (!$propertyData->hasAnyValue())) {
                continue;
            }

            $relationshipData->addProperty($propertyData);
        }

        // Start node

        foreach ($this->getChildrenByTagName($domNode, 'start') as $domSubNode) {
            foreach ($this->getChildrenByTagName($domSubNode, 'node') as $nodeNode) {
                $relationshipData->setStartNode($this->getNodeData($nodeNode));
            }
        }

        // End node

        foreach ($this->getChildrenByTagName($domNode, 'end') as $domSubNode) {
            foreach ($this->getChildrenByTagName($domSubNode, 'node') as $nodeNode) {
                $relationshipData->setEndNode($this->getNodeData($nodeNode));
            }
        }

        return $relationshipData;
    }


    /**
     * @param DOMElement $domNode
     * @return PropertyData
     */
    protected function getPropertyData(DOMElement $domNode): PropertyData
    {
        /*
        Example:

        <property name="NAME1" type="string">VALUE</property>

        List of values:

        <property name="NAME1" type="string">
          <value>VALUE1</value>
          <value>VALUE2</value>
        </property>
        */

        $propertyData = new PropertyData();

        if ($domNode->hasAttribute('name')) {
            $propertyData->setName(trim($domNode->getAttribute('name')));
        }

        if ($domNode->hasAttribute('type')) {
            $propertyData->setType(trim($domNode->getAttribute('type')));
        }

        $domSubNodes = $this->getChildrenByTagName($domNode, 'value');

        if (count($domSubNodes) === 0) {
            $propertyData->setValue($this->stringToTypedValue($domNode->nodeValue, $propertyData->getType()));
        } else {
            foreach ($domSubNodes as $domSubNode) {
                $propertyData->setValueList(array_merge(
                    $propertyData->getValueList(),
                    [$this->stringToTypedValue($domSubNode->nodeValue, $propertyData->getType())]
                ));
            }
        }

        return $propertyData;
    }


    /**
     * @param mixed $value
     * @param string $type
     * @return bool|float|int|\Laudis\Neo4j\Types\Date|\Laudis\Neo4j\Types\DateTime|\Laudis\Neo4j\Types\Time|string
     */
    protected function stringToTypedValue(string $value, string $type)
    {
        $value = trim($value);

        // Names of types taken from https://github.com/neo4j-php/neo4j-php-client#accessing-the-results

        switch (strtolower($type)) {
            case PropertyData::TYPE_INTEGER:
                $value = intval($value);
                break;
            case PropertyData::TYPE_FLOAT:
                $value = floatval($value);
                break;
            case PropertyData::TYPE_BOOLEAN:
                $value = boolval($value);
                break;
            case PropertyData::TYPE_DATE:
                $value = Converter::stringToNeo4jDate($value);
                break;
            case PropertyData::TYPE_TIME:
                $value = Converter::stringToNeo4jTime($value);
                break;
            case PropertyData::TYPE_DATETIME:
                $value = Converter::stringToNeo4jDateTime($value);
                break;
        }

        return $value;
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