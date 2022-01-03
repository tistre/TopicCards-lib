<?php

namespace TopicCards\Import;


use DateTimeImmutable;
use DOMElement;
use Laudis\Neo4j\Types\Date;
use Laudis\Neo4j\Types\DateTime;
use Laudis\Neo4j\Types\Time;

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

        foreach ($this->getChildrenByTagName($domNode, 'label') as $domSubNode) {
            $label = trim($domSubNode->nodeValue);

            if (strlen($label) === 0) {
                continue;
            }

            $nodeData->labels[] = $label;
        }

        // Properties

        foreach ($this->getChildrenByTagName($domNode, 'property') as $domSubNode) {
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

        foreach ($this->getChildrenByTagName($domNode, 'type') as $domSubNode) {
            $relationshipData->type = trim($domSubNode->nodeValue);
        }

        // Properties

        foreach ($this->getChildrenByTagName($domNode, 'property') as $domSubNode) {
            $propertyData = $this->getPropertyData($domSubNode);

            if ((strlen($propertyData->name) === 0) || (count($propertyData->values) === 0)) {
                continue;
            }

            $relationshipData->properties[] = $propertyData;
        }

        // Start node

        foreach ($this->getChildrenByTagName($domNode, 'start') as $domSubNode) {
            foreach ($this->getChildrenByTagName($domSubNode, 'node') as $nodeNode) {
                $relationshipData->startNode = $this->getNodeData($nodeNode);
            }
        }

        // End node

        foreach ($this->getChildrenByTagName($domNode, 'end') as $domSubNode) {
            foreach ($this->getChildrenByTagName($domSubNode, 'node') as $nodeNode) {
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

        foreach ($this->getChildrenByTagName($domNode, 'value') as $domSubNode) {
            $value = trim($domSubNode->nodeValue);

            // Names of types taken from https://github.com/neo4j-php/neo4j-php-client#accessing-the-results

            switch (strtolower($propertyData->type)) {
                case 'integer':
                    $value = intval($value);
                    break;
                case 'float':
                    $value = floatval($value);
                    break;
                case 'boolean':
                    $value = boolval($value);
                    break;
                case 'date':
                    // Date objects need days since Unix epoch
                    $interval = (new DateTimeImmutable('1970-01-01'))->diff(new DateTimeImmutable($value));
                    $value = new Date(intval($interval->format('%a')));
                    break;
                case 'time':
                    // Time objects need seconds since Unix epoch
                    $value = new Time((new DateTimeImmutable($value))->format('U'));
                    break;
                case 'datetime':
                    $dt = new DateTimeImmutable($value);
                    $value = new DateTime($dt->format('U'), $dt->format('u') * 1000, $dt->format('Z'));
                    break;
            }

            $propertyData->values[] = $value;
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