<?php

namespace TopicCards\Utils;


class DataTypeUtils
{
    const DATATYPE_BOOLEAN = 'http://www.w3.org/2001/XMLSchema#boolean';
    const DATATYPE_HTML = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#HTML';
    const DATATYPE_NON_NEGATIVE_INTEGER = 'http://www.w3.org/2001/XMLSchema#nonNegativeInteger';
    const DATATYPE_STRING = 'http://www.w3.org/2001/XMLSchema#string';
    const DATATYPE_URI = 'http://www.w3.org/2001/XMLSchema#anyURI';
    const DATATYPE_XHTML = 'http://www.w3.org/1999/xhtml';
    const DATATYPE_XML = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#XMLLiteral';


    public static function validate(&$value, $dataType, &$errorMsg)
    {
        $errorMsg = '';

        if ($dataType === self::DATATYPE_XHTML) {
            return self::validateXhtml($value, $dataType, $errorMsg);
        } elseif (self::isXml($dataType)) {
            return self::validateXml($value, $dataType, $errorMsg);
        }

        return 1;
    }


    protected static function validateXhtml(&$value, $dataType, &$errorMsg)
    {
        // XHTML value can be "hello <i>world</i>", need to wrap it in a div
        // to validate

        $xml_value = self::valueToXml($value, $dataType);

        return self::validateXml($xml_value, $dataType, $errorMsg);
    }


    protected static function validateXml(&$value, $dataType, &$errorMsg)
    {
        libxml_use_internal_errors(true);

        $dom = new \DOMDocument;
        $dom->loadXML($value);

        $errors = libxml_get_errors();

        libxml_clear_errors();
        libxml_use_internal_errors(false);

        if (count($errors) === 0) {
            return 1;
        }

        $msgs = [];

        foreach ($errors as $error) {
            $msgs[] = sprintf
            (
                'XML error %s: %s, line %s, column %s',
                $error->code,
                trim(strtr($error->message, "\n\r", '  ')),
                $error->line,
                $error->column
            );
        }

        $errorMsg = implode('. ', $msgs);

        return -1;
    }


    public static function valueToXml($value, $dataType)
    {
        if ($dataType === self::DATATYPE_XHTML) {
            return sprintf
            (
                '<div xmlns="http://www.w3.org/1999/xhtml">%s</div>',
                $value
            );
        } elseif (self::isXml($dataType)) {
            return $value;
        } else {
            return htmlspecialchars($value);
        }
    }


    public static function getValueFromDomNode(\DOMElement $contextNode, $dataType)
    {
        if ($dataType === self::DATATYPE_XHTML) {
            // XHTML content is wrapped in a <div>

            $xhtml = '';

            foreach ($contextNode->childNodes as $node) {
                if ($node->nodeType != XML_ELEMENT_NODE) {
                    continue;
                }

                if ($node->tagName !== 'div') {
                    continue;
                }

                $xhtml = self::getNodeXml($node);
            }

            return $xhtml;
        } elseif (self::isXml($dataType)) {
            return self::getNodeXml($contextNode);
        } else {
            return $contextNode->nodeValue;
        }
    }


    protected static function getNodeXml(\DOMElement $node)
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');

        $nodeCopy = $dom->importNode($node, true);
        $dom->appendChild($nodeCopy);

        $xml = $dom->saveXML($nodeCopy);

        $start = strpos($xml, '>') + 1;
        $length = strrpos($xml, '<') - $start;

        return substr($xml, $start, $length);
    }


    public static function isXml($dataType)
    {
        // XXX What are the datatype URIs for application/xml, something+xml etc.?

        $xmlDataTypes =
            [
                'http://www.w3.org/1999/02/22-rdf-syntax-ns#XMLLiteral',
                'http://www.w3.org/2001/XMLSchema#anyType'
            ];

        return in_array($dataType, $xmlDataTypes);
    }
}
