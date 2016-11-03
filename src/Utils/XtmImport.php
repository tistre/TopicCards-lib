<?php

namespace TopicCards\Utils;

use TopicCards\Interfaces\AssociationInterface;
use TopicCards\Interfaces\TopicInterface;
use TopicCards\Interfaces\TopicMapInterface;


class XtmImport
{
    /** @var TopicMapInterface */
    protected $topicmap;
    
    protected $generated_guids = [ ];
    
    
    public function __construct(TopicMapInterface $topicmap)
    {
        $this->topicmap = $topicmap;
    }
    
    
    public function importObjects($xml)
    {        
        $dom = new \DOMDocument();
        
        $ok = $dom->loadXML($xml);
        
        if ($ok === false)
            return false;

        $result = [ ];
        
        foreach ($dom->documentElement->childNodes as $node)
        {
            if ($node->nodeType != XML_ELEMENT_NODE)
                continue;
                
            if ($node->tagName === 'topic')
            {
                $result[ ] = $this->importTopic($node);
            }
            elseif ($node->tagName === 'association')
            {
                $result[ ] = $this->importAssociation($node);
            }
        }
        
        return $result;
    }
    
    
    public function importTopic(\DOMElement $context_node)
    {
        $topic = $this->topicmap->newTopic();

        if ($context_node->hasAttribute('id'))
            $topic->setId($this->generateGuid($context_node->getAttribute('id')));

        $this->importTypes($context_node, $topic);
        $this->importSubjectIdentifiers($context_node, $topic);
        $this->importSubjectLocators($context_node, $topic);
        $this->importNames($context_node, $topic);
        $this->importOccurrences($context_node, $topic);
        
        return $topic;
    }
    
    
    public function importAssociation(\DOMElement $context_node)
    {
        $association = $this->topicmap->newAssociation();

        if ($context_node->hasAttribute('id'))
            $association->setId($this->generateGuid($context_node->getAttribute('id')));

        $association->setReifierId($this->getReifier($context_node));
        $association->setTypeId($this->getType($context_node));
        $association->setScopeIds($this->getScope($context_node));

        $this->importRoles($context_node, $association);
        
        return $association;
    }
    
    
    protected function importTypes(\DOMElement $context_node, TopicInterface $topic)
    {
        $topic_refs = [ ];
        
        foreach ($context_node->getElementsByTagName('instanceOf') as $node)
        {
            $topic_ref = $this->getTopicRef($node);
            
            if (strlen($topic_ref) > 0)
                $topic_refs[ ] = $topic_ref;
        }
        
        $topic->setTypeIds($topic_refs);
    }
    
    
    protected function importSubjectIdentifiers(\DOMElement $context_node, TopicInterface $topic)
    {
        $this->importSubjects('subjectIdentifier', $context_node, $topic);
    }
    
    
    protected function importSubjectLocators(\DOMElement $context_node, TopicInterface $topic)
    {
        $this->importSubjects('subjectLocator', $context_node, $topic);
    }
    
    
    protected function importSubjects($what, \DOMElement $context_node, TopicInterface $topic)
    {
        $hrefs = [ ];
        
        foreach ($context_node->getElementsByTagName($what) as $node)
        {
            /** @var \DOMElement $node */
            
            if (! $node->hasAttribute('href'))
                continue;
                
            $hrefs[ ] = $node->getAttribute('href');
        }
        
        $method = sprintf('set%ss', $what);
        
        $topic->$method($hrefs);
    }


    protected function importNames(\DOMElement $context_node, TopicInterface $topic)
    {
        $names = [ ];
        
        foreach ($context_node->getElementsByTagName('name') as $node)
        {
            /** @var \DOMElement $node */
            
            $name = $topic->newName();
            
            $name->setReifierId($this->getReifier($node));
            $name->setTypeId($this->getType($node));
            $name->setScopeIds($this->getScope($node));
            
            foreach ($node->getElementsByTagName('value') as $subnode)
                $name->setValue($subnode->nodeValue);

            $names[ ] = $name;
        }
        
        $topic->setNames($names);
    }
    
    
    protected function importOccurrences(\DOMElement $context_node, TopicInterface $topic)
    {
        $occurrences = [ ];
        
        foreach ($context_node->getElementsByTagName('occurrence') as $node)
        {
            /** @var \DOMElement $node */
            
            $occurrence = $topic->newOccurrence();
            
            $occurrence->setReifierId($this->getReifier($node));
            $occurrence->setTypeId($this->getType($node));
            $occurrence->setScopeIds($this->getScope($node));
            
            foreach ($node->getElementsByTagName('resourceData') as $subnode)
            {
                /** @var \DOMElement $subnode */
                
                $datatype = $subnode->getAttribute('datatype');
                
                $value = DatatypeUtils::getValueFromDomNode($subnode, $datatype);
                
                if (strlen($value) === 0)
                    continue 2;
                    
                $occurrence->setValue($value);                
                $occurrence->setDatatype($datatype);
                
                break;
            }
                
            $occurrences[ ] = $occurrence;
        }
        
        $topic->setOccurrences($occurrences);
    }
    
    
    protected function importRoles(\DOMElement $context_node, AssociationInterface $association)
    {
        $roles = [ ];
        
        foreach ($context_node->getElementsByTagName('role') as $node)
        {
            $role = $association->newRole();
            
            $role->setReifierId($this->getReifier($node));
            $role->setTypeId($this->getType($node));
            $role->setPlayerId($this->getTopicRef($node));
            
            $roles[ ] = $role;
        }
        
        $association->setRoles($roles);
    }
    
    
    protected function getReifier(\DOMElement $node)
    {
        foreach ($node->getElementsByTagName('reifier') as $subnode)
            return $this->getTopicRef($subnode);
        
        return false;
    }


    protected function getType(\DOMElement $node)
    {
        foreach ($node->getElementsByTagName('type') as $subnode)
            return $this->getTopicRef($subnode);
        
        return false;
    }


    protected function getScope(\DOMElement $node)
    {
        $result = [ ];
        
        foreach ($node->getElementsByTagName('scope') as $subnode)
        {
            $scope = $this->getTopicRef($subnode);
            
            if (strlen($scope) > 0)
            {
                $result[ ] = $scope;
            }
        }
        
        return $result;
    }


    protected function getTopicRef(\DOMElement $node)
    {
        foreach ($node->childNodes as $subnode)
        {
            /** @var \DOMElement $subnode */
            
            if ($subnode->nodeType !== XML_ELEMENT_NODE)
            {
                continue;
            }
                
            if 
            (
                (($subnode->tagName === 'subjectIdentifierRef') || ($subnode->tagName === 'subjectLocatorRef'))
                && $subnode->hasAttribute('href') 
                && (strlen($subnode->getAttribute('href')) > 0)
            )
            {
                $topic_id = $this->topicmap->getTopicIdBySubject($subnode->getAttribute('href'), true);
                
                if (strlen($topic_id) > 0)
                {
                    return $topic_id;
                }
            }
            elseif 
            (
                ($subnode->tagName === 'topicRef') 
                && $subnode->hasAttribute('href') 
                && (strlen($subnode->getAttribute('href')) > 0)
            )
            {                
                $href = $subnode->getAttribute('href');
                
                if (substr($href, 0, 1) === '#')
                {
                    // XXX assuming local IDs are prefixed with "#"
                    
                    $topic_ref = $this->generateGuid($href);
                    return substr($topic_ref, 1);
                }
                else
                {
                    // Subject identifier or locator
                    
                    $topic_id = $this->topicmap->getTopicIdBySubject($href, true);
                
                    if (strlen($topic_id) > 0)
                    {
                        return $topic_id;
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

        if (strlen($id) === 0)
            return $id;
        
        $prefix = '';
        
        if ($id[ 0 ] === '#')
        {
            $prefix = '#';
            $id = substr($id, 1);
        }
        
        if (substr($id, 0, 25) !== 'topiccards-generate-uuid:')
            return $prefix . $id;

        $key = substr($id, 25);
        
        if (! isset($this->generated_guids[ $key ]))
            $this->generated_guids[ $key ] = $this->topicmap->createId();

        return $prefix . $this->generated_guids[ $key ];
    }    
}
