<?php

namespace StrehleDe\TopicCards\Import;

use DOMElement;
use StrehleDe\TopicCards\Cypher\StatementTemplate;


class GraphXmlImporter
{
    /**
     * @param DOMElement $domNode
     * @return StatementTemplate
     */
    public static function getStatementTemplate(DOMElement $domNode): StatementTemplate
    {
        /*
        Example:

        <graph xmlns="https://topiccards.net/GraphCards/xmlns">
            <statement>
                <text>
                    MERGE (n{{ label1 }} { uuid: {{ uuid }} })
                    SET n{{ label2 }}
                    SET n = {
                        name: {{ name }},
                        sameAs: {{ sameAs }},
                        born: date({{ born }}),
                        age: {{ age }}
                    }
                    RETURN n.uuid
                </text>
                <labels>
                    <label name="label1">Person</label>
                    <label name="label2">Boy</label>
                    <label name="label2">Man</label>
                </labels>
                <parameters>
                    <parameter name="uuid">5e02f650-7429-4dab-b57b-844bddce068b</parameter>
                    <parameter name="name" type="string">Tim</parameter>
                    <parameter name="sameAs" type="string">
                        <value>https://www.strehle.de/tim/</value>
                        <value>https://twitter.com/tistre</value>
                    </parameter>
                    <parameter name="born">1972-10-01</parameter>
                    <parameter name="age" type="integer">49</parameter>
                </parameters>
            </statement>
        </graph>
        */

        // <text>

        $text = '';

        foreach (self::getChildrenByTagName($domNode, 'text') as $domSubNode) {
            $text = trim($domSubNode->nodeValue);
            break;
        }

        $statementTemplate = new StatementTemplate($text, [], []);

        // <labels>

        foreach (self::getChildrenByTagName($domNode, 'labels') as $domIntermediateNode) {
            foreach (self::getChildrenByTagName($domIntermediateNode, 'label') as $domSubNode) {
                if (!$domSubNode->hasAttribute('name')) {
                    continue;
                }

                $labelName = trim($domSubNode->getAttribute('name'));
                $labelValue = trim($domSubNode->nodeValue);

                if ((strlen($labelName) === 0) || (strlen($labelValue) === 0)) {
                    continue;
                }

                $statementTemplate->addLabel($labelName, $labelValue);

            }
        }

        // <parameters>

        foreach (self::getChildrenByTagName($domNode, 'parameters') as $domIntermediateNode) {
            foreach (self::getChildrenByTagName($domIntermediateNode, 'parameter') as $domSubNode) {
                $parameter = self::getParameter($domSubNode);

                if (strlen($parameter->getName()) === 0) {
                    continue;
                }

                $statementTemplate->setParameter($parameter->getName(), $parameter->getValue());
            }
        }

        return $statementTemplate;
    }


    /**
     * @param DOMElement $domNode
     * @return GraphXmlParameter
     */
    protected static function getParameter(DOMElement $domNode): GraphXmlParameter
    {
        /*
        Example:

        <parameter name="name" type="string">Tim</parameter>

        List of values:

        <parameter name="sameAs" type="string">
            <value>https://www.strehle.de/tim/</value>
            <value>https://twitter.com/tistre</value>
        </parameter>
        */

        $name = '';
        $type = GraphXmlParameter::TYPE_STRING;

        if ($domNode->hasAttribute('name')) {
            $name = trim($domNode->getAttribute('name'));
        }

        if ($domNode->hasAttribute('type')) {
            $type = trim($domNode->getAttribute('type'));
        }

        $domSubNodes = self::getChildrenByTagName($domNode, 'value');

        if (count($domSubNodes) === 0) {
            $value = trim($domNode->nodeValue);
        } else {
            $value = [];

            foreach ($domSubNodes as $domSubNode) {
                $value[] = trim($domSubNode->nodeValue);
            }
        }

        return new GraphXmlParameter($name, $value, $type);
    }


    /**
     * @param DOMElement $domNode
     * @param string $tagName
     * @return DOMElement[]
     */
    protected static function getChildrenByTagName(DOMElement $domNode, string $tagName): array
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