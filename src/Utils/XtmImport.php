<?php

namespace TopicCards\Utils;

use TopicCards\Interfaces\AssociationInterface;
use TopicCards\Interfaces\TopicInterface;
use TopicCards\Interfaces\TopicMapInterface;


class XtmImport
{
    /** @var TopicMapInterface */
    protected $topicMap;

    protected $generatedGuids = [];


    public function __construct(TopicMapInterface $topicMap)
    {
        $this->topicMap = $topicMap;
    }


    public function importObjects($xml)
    {
        $dom = new \DOMDocument();

        $ok = $dom->loadXML($xml);

        if ($ok === false) {
            return false;
        }

        $result = [];

        foreach ($dom->documentElement->childNodes as $node) {
            if ($node->nodeType != XML_ELEMENT_NODE) {
                continue;
            }

            if ($node->tagName === 'topic') {
                $result[] = $this->importTopic($node);
            } elseif ($node->tagName === 'association') {
                $result[] = $this->importAssociation($node);
            }
        }

        return $result;
    }


    public function importTopic(\DOMElement $contextNode)
    {
        $topic = $this->topicMap->newTopic();

        if ($contextNode->hasAttribute('id')) {
            $topic->setId($this->generateGuid($contextNode->getAttribute('id')));
        }

        $this->importTypes($contextNode, $topic);
        $this->importSubjectIdentifiers($contextNode, $topic);
        $this->importSubjectLocators($contextNode, $topic);
        $this->importNames($contextNode, $topic);
        $this->importOccurrences($contextNode, $topic);

        return $topic;
    }


    public function importAssociation(\DOMElement $contextNode)
    {
        $association = $this->topicMap->newAssociation();

        if ($contextNode->hasAttribute('id')) {
            $association->setId($this->generateGuid($contextNode->getAttribute('id')));
        }

        $association->setReifierId($this->getReifier($contextNode));
        $association->setTypeId($this->getType($contextNode));
        $association->setScopeIds($this->getScope($contextNode));

        $this->importRoles($contextNode, $association);

        return $association;
    }


    protected function importTypes(\DOMElement $contextNode, TopicInterface $topic)
    {
        $topicRefs = [];

        foreach ($contextNode->getElementsByTagName('instanceOf') as $node) {
            $topicRef = $this->getTopicRef($node);

            if (strlen($topicRef) > 0) {
                $topicRefs[] = $topicRef;
            }
        }

        $topic->setTypeIds($topicRefs);
    }


    protected function importSubjectIdentifiers(\DOMElement $contextNode, TopicInterface $topic)
    {
        $this->importSubjects('subjectIdentifier', $contextNode, $topic);
    }


    protected function importSubjectLocators(\DOMElement $contextNode, TopicInterface $topic)
    {
        $this->importSubjects('subjectLocator', $contextNode, $topic);
    }


    protected function importSubjects($what, \DOMElement $contextNode, TopicInterface $topic)
    {
        $hrefs = [];

        foreach ($contextNode->getElementsByTagName($what) as $node) {
            /** @var \DOMElement $node */

            if (! $node->hasAttribute('href')) {
                continue;
            }

            $hrefs[] = $node->getAttribute('href');
        }

        $method = sprintf('set%ss', $what);

        $topic->$method($hrefs);
    }


    protected function importNames(\DOMElement $contextNode, TopicInterface $topic)
    {
        $names = [];

        foreach ($contextNode->getElementsByTagName('name') as $node) {
            /** @var \DOMElement $node */

            $name = $topic->newName();

            $name->setReifierId($this->getReifier($node));
            $name->setTypeId($this->getType($node));
            $name->setScopeIds($this->getScope($node));

            foreach ($node->getElementsByTagName('value') as $subNode) {
                $name->setValue($subNode->nodeValue);
            }

            $names[] = $name;
        }

        $topic->setNames($names);
    }


    protected function importOccurrences(\DOMElement $contextNode, TopicInterface $topic)
    {
        $occurrences = [];

        foreach ($contextNode->getElementsByTagName('occurrence') as $node) {
            /** @var \DOMElement $node */

            $occurrence = $topic->newOccurrence();

            $occurrence->setReifierId($this->getReifier($node));
            $occurrence->setTypeId($this->getType($node));
            $occurrence->setScopeIds($this->getScope($node));

            foreach ($node->getElementsByTagName('resourceData') as $subNode) {
                /** @var \DOMElement $subNode */

                $dataType = $subNode->getAttribute('datatype');

                $value = DataTypeUtils::getValueFromDomNode($subNode, $dataType);

                if (strlen($value) === 0) {
                    continue 2;
                }

                $occurrence->setValue($value);
                $occurrence->setDataType($dataType);

                break;
            }

            $occurrences[] = $occurrence;
        }

        $topic->setOccurrences($occurrences);
    }


    protected function importRoles(\DOMElement $contextNode, AssociationInterface $association)
    {
        $roles = [];

        foreach ($contextNode->getElementsByTagName('role') as $node) {
            $role = $association->newRole();

            $role->setReifierId($this->getReifier($node));
            $role->setTypeId($this->getType($node));
            $role->setPlayerId($this->getTopicRef($node));

            $roles[] = $role;
        }

        $association->setRoles($roles);
    }


    protected function getReifier(\DOMElement $node)
    {
        foreach ($node->getElementsByTagName('reifier') as $subNode) {
            return $this->getTopicRef($subNode);
        }

        return false;
    }


    protected function getType(\DOMElement $node)
    {
        foreach ($node->getElementsByTagName('type') as $subNode) {
            return $this->getTopicRef($subNode);
        }

        return false;
    }


    protected function getScope(\DOMElement $node)
    {
        $result = [];

        foreach ($node->getElementsByTagName('scope') as $subNode) {
            $scope = $this->getTopicRef($subNode);

            if (strlen($scope) > 0) {
                $result[] = $scope;
            }
        }

        return $result;
    }


    protected function getTopicRef(\DOMElement $node)
    {
        foreach ($node->childNodes as $subNode) {
            /** @var \DOMElement $subNode */

            if ($subNode->nodeType !== XML_ELEMENT_NODE) {
                continue;
            }

            if
            (
                (($subNode->tagName === 'subjectIdentifierRef') || ($subNode->tagName === 'subjectLocatorRef'))
                && $subNode->hasAttribute('href')
                && (strlen($subNode->getAttribute('href')) > 0)
            ) {
                $topicId = $this->topicMap->getTopicIdBySubject($subNode->getAttribute('href'), true);

                if (strlen($topicId) > 0) {
                    return $topicId;
                }
            } elseif
            (
                ($subNode->tagName === 'topicRef')
                && $subNode->hasAttribute('href')
                && (strlen($subNode->getAttribute('href')) > 0)
            ) {
                $href = $subNode->getAttribute('href');

                if (substr($href, 0, 1) === '#') {
                    // XXX assuming local IDs are prefixed with "#"

                    $topicRef = $this->generateGuid($href);

                    return substr($topicRef, 1);
                } else {
                    // Subject identifier or locator

                    $topicId = $this->topicMap->getTopicIdBySubject($href, true);

                    if (strlen($topicId) > 0) {
                        return $topicId;
                    }

                    return $href;
                }
            }
        }

        return false;
    }


    protected function generateGuid($id)
    {
        // #topiccards-generate-uuid:idm38524599744 => 7b1931ef-d101-4a7b-81de-b174ab7872df
        // topiccards-generate-uuid:idm38524599744 => 7b1931ef-d101-4a7b-81de-b174ab7872df

        if (strlen($id) === 0) {
            return $id;
        }

        $prefix = '';

        if ($id[0] === '#') {
            $prefix = '#';
            $id = substr($id, 1);
        }

        if (substr($id, 0, 25) !== 'topiccards-generate-uuid:') {
            return $prefix . $id;
        }

        $key = substr($id, 25);

        if (! isset($this->generatedGuids[$key])) {
            $this->generatedGuids[$key] = $this->topicMap->createId();
        }

        return $prefix . $this->generatedGuids[$key];
    }
}
