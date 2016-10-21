<?php

namespace TopicCards\Model;

use TopicCards\Db\TopicDbAdapter;
use TopicCards\Interfaces\iName;
use TopicCards\Interfaces\iOccurrence;
use TopicCards\Interfaces\iPersistentSearchAdapter;
use TopicCards\Interfaces\iTopic;
use TopicCards\Interfaces\iTopicDbAdapter;
use TopicCards\Interfaces\iTopicMap;
use TopicCards\Search\TopicSearchAdapter;


class Topic extends Core implements iTopic
{
    use Persistent;
    
    protected $subject_identifiers = [ ];
    protected $subject_locators = [ ];
    protected $types = [ ];
    
    /** @var iName[] */
    protected $names = [ ];
    
    /** @var iOccurrence[] */
    protected $occurrences = [ ];
    
    protected $reifies_what = '';
    protected $reifies_id = '';
    
    /** @var iTopicDbAdapter */
    protected $db_adapter;
    
    /** @var iPersistentSearchAdapter */
    protected $search_adapter;
    

    /**
     * Topic constructor.
     *
     * @param iTopicMap $topicmap
     */
    public function __construct(iTopicMap $topicmap)
    {
        parent::__construct($topicmap);
        
        $this->db_adapter = new TopicDbAdapter($this);
        $this->search_adapter = new TopicSearchAdapter($this);
    }


    /**
     * @return iTopicDbAdapter
     */
    public function getDbAdapter()
    {
        return $this->db_adapter;
    }


    /**
     * @return iPersistentSearchAdapter
     */
    public function getSearchAdapter()
    {
        return $this->search_adapter;
    }


    public function getSubjectIdentifiers()
    {
        return $this->subject_identifiers;
    }
    
    
    public function setSubjectIdentifiers(array $strings)
    {
        $this->subject_identifiers = $strings;
        return 1;
    }
    
    
    public function getSubjectLocators()
    {
        return $this->subject_locators;
    }
    
    
    public function setSubjectLocators(array $strings)
    {
        $this->subject_locators = $strings;
        return 1;
    }
    
    
    public function getTypeIds()
    {
        return $this->types;
    }
    
    
    public function setTypeIds(array $topic_ids)
    {
        $this->types = $topic_ids;        
        return 1;
    }
    

    public function getTypes()
    {
        $result = [ ];
        
        foreach ($this->getTypeIds() as $topic_id)
            $result[ ] = $this->topicmap->getTopicSubject($topic_id);
            
        return $result;
    }
    
    
    public function setTypes(array $topic_subjects)
    {
        $topic_ids = [ ];
        $result = 1;
        
        foreach ($topic_subjects as $topic_subject)
        {
            $topic_id = $this->topicmap->getTopicIdBySubject($topic_subject, true);
            
            if (strlen($topic_id) === 0)
            {
                $result = -1;
            }
            else
            {
                $topic_ids[ ] = $topic_id;
            }   
        }
        
        $ok = $this->setTypeIds($topic_ids);
        
        if ($ok < 0)
            $result = $ok;
        
        return $result;
    }
    
        
    public function hasTypeId($topic_id)
    {
        return in_array($topic_id, $this->types);
    }
    
    
    public function hasType($topic_subject)
    {
        return $this->hasTypeId($this->topicmap->getTopicIdBySubject($topic_subject));
    }
    

    public function newName()
    {   
        $name = new Name($this->topicmap);
        
        $this->names[ ] = $name;
        
        return $name;
    }


    /**
     * @param array $filters
     * @return iName[]
     */
    public function getNames(array $filters = [ ])
    {
        if (count($filters) === 0)            
        {
            return $this->names;
        }
        
        $result = [ ];
        
        if (isset($filters[ 'type' ]))
        {
            $filters[ 'type_id' ] = $this->topicmap->getTopicIdBySubject($filters[ 'type' ]);
        }

        foreach ($this->names as $name)
        {
            if (isset($filters[ 'id' ]))
            {
                if ($name->getId() !== $filters[ 'id' ])
                    continue;
            }

            if (isset($filters[ 'value' ]))
            {
                if ($name->getValue() !== $filters[ 'value' ])
                    continue;
            }

            if (isset($filters[ 'type_id' ]))
            {
                if ($name->getTypeId() !== $filters[ 'type_id' ])
                    continue;
            }

            if (isset($filters[ 'reifier' ]))
            {
                if ($name->getReifierId() !== $filters[ 'reifier' ])
                    continue;
            }

            $result[ ] = $name;
        }
        
        return $result;
    }
    
    
    public function getFirstName(array $filters = [ ])
    {
        $names = $this->getNames($filters);
        
        if (count($names) > 0)
            return $names[ 0 ];

        $name = $this->newName();
        
        if (isset($filters[ 'type' ]))
        {
            $name->setType($filters[ 'type' ]);
        }
        elseif (isset($filters[ 'type_id' ]))
        {
            $name->setTypeId($filters[ 'type_id' ]);
        }
        elseif (isset($filters[ 'id' ]))
        {
            $name->setId($filters[ 'id' ]);
        }
        
        return $name;
    }
    
    
    public function setNames(array $names)
    {
        $this->names = $names;
        return 1;
    }
    

    public function getLabel($preferred_scopes = false)
    {
        if (! is_array($preferred_scopes))
        {
            $preferred_scopes = $this->topicmap->getPreferredLabelScopes();
        }
            
        // Preferred scopes in ascending order.
        // Prefer http://schema.org/name ("default"), otherwise use first name

        $by_scope = array_fill_keys
        (
            array_keys($preferred_scopes), 
            [ 'default' => [ ], 'other' => [ ] ]
        );

        foreach ($this->getNames([ ]) as $name)
        {
            // XXX make http://schema.org/name a constant (DEFAULT_NAME_SUBJECT)
            $type_key = 
            (
                ($name->getType() === 'http://schema.org/name')
                ? 'default'
                : 'other'
            );

            foreach ($preferred_scopes as $scope_key => $scope)
            {
                if (($scope !== '*') && (! $name->matchesScope($scope)))
                    continue;
                
                $value = $name->getValue();
                
                if (strlen($value) === 0)
                    continue;
                    
                $by_scope[ $scope_key ][ $type_key ][ ] = $value;
            }
        }

        foreach ($by_scope as $scope_key => $by_type)
        {
            foreach ($by_type as $values)
            {
                if (isset($values[ 0 ]))
                    return $values[ 0 ];
            }
        }

        foreach ($this->getSubjectIdentifiers() as $value)
        {
            return $value;
        }
        
        return '';
    }

    
    public function newOccurrence()
    {   
        $occurrence = new Occurrence($this->topicmap);
        
        $this->occurrences[ ] = $occurrence;
        
        return $occurrence;
    }


    /**
     * @param array $filters
     * @return iOccurrence[]
     */
    public function getOccurrences(array $filters = [ ])
    {
        if (count($filters) === 0)
            return $this->occurrences;
            
        if (isset($filters[ 'type' ]))
            $filters[ 'type_id' ] = $this->topicmap->getTopicIdBySubject($filters[ 'type' ]);

        $result = [ ];
        
        foreach ($this->occurrences as $occurrence)
        {
            if (isset($filters[ 'type_id' ]))
            {
                if ($occurrence->getTypeId() !== $filters[ 'type_id' ])
                    continue;
            }

            if (isset($filters[ 'id' ]))
            {
                if ($occurrence->getId() !== $filters[ 'id' ])
                    continue;
            }

            if (isset($filters[ 'value' ]))
            {
                if ($occurrence->getValue() !== $filters[ 'value' ])
                    continue;
            }
            
            $result[ ] = $occurrence;
        }
        
        return $result;
    }
    
    
    public function getFirstOccurrence(array $filters = [ ])
    {
        $occurrences = $this->getOccurrences($filters);
        
        if (count($occurrences) > 0)
            return $occurrences[ 0 ];

        $occurrence = $this->newOccurrence();
        
        if (isset($filters[ 'type' ]))
        {
            $occurrence->setType($filters[ 'type' ]);
        }
        elseif (isset($filters[ 'type_id' ]))
        {
            $occurrence->setTypeId($filters[ 'type_id' ]);
        }
        elseif (isset($filters[ 'id' ]))
        {
            $occurrence->setId($filters[ 'id' ]);
        }
        
        return $occurrence;
    }
    
    
    public function setOccurrences(array $occurrences)
    {
        $this->occurrences = $occurrences;
        return 1;
    }


    /**
     * @return string
     */
    public function getReifiesWhat()
    {
        return $this->reifies_what;
    }


    /**
     * @param string $reifies_what
     */
    public function setReifiesWhat($reifies_what)
    {
        $this->reifies_what = $reifies_what;
    }


    /**
     * @return string
     */
    public function getReifiesId()
    {
        return $this->reifies_id;
    }


    /**
     * @param string $reifies_id
     */
    public function setReifiesId($reifies_id)
    {
        $this->reifies_id = $reifies_id;
    }

    
    public function isReifier(&$reifies_what, &$reifies_id)
    {
        $reifies_what = $this->getReifiesWhat();
        $reifies_id = $this->getReifiesId();
        
        return (($reifies_what !== iTopic::REIFIES_NONE) && (strlen($reifies_id) > 0));
    }
    

    public function getReifiedObject($reifies_what)
    {
        return $this->db_adapter->selectReifiedObject($reifies_what);
    }
    
    
    public function validate(&$msg_html)
    {
        $result = 1;
        $msg_html = '';

        // We want unique subject identifiers, but Neo4j constraints cannot
        // uniquely index multi-valued properties. So let's check for ourselves (yes,
        // there's room for race conditions here.)

        if ($this->getVersion() === 0)
        {
            foreach ($this->getSubjectIdentifiers() as $subject_identifier)
            {
                if (strlen($this->topicmap->getTopicIdBySubject($subject_identifier)) > 0)
                {
                    $result = iTopic::ERR_SUBJECT_IDENTIFIER_EXISTS;
                    $msg_html .= 'Subject identifier already exists.';
                }
            }
        }
        
        foreach (array_merge($this->getNames([ ]), $this->getOccurrences([ ])) as $obj)
        {
            $ok = $obj->validate($msg);
            
            if ($ok < 0)
            {
                $result = $ok;
                $msg_html .= $msg;
            }
        }
        
        return $result;
    }
    
    
    public function getAll()
    {   
        $result = 
        [
            'types' => $this->getTypeIds(), 
            'subject_identifiers' => $this->getSubjectIdentifiers(), 
            'subject_locators' => $this->getSubjectLocators(), 
            'names' => [ ], 
            'occurrences' => [ ],
            'reifies_what' => $this->getReifiesWhat(),
            'reifies_id' => $this->getReifiesId()
        ];
        
        foreach ($this->names as $name)
            $result[ 'names' ][ ] = $name->getAll();
        
        foreach ($this->occurrences as $occurrence)
            $result[ 'occurrences' ][ ] = $occurrence->getAll();
        
        $result = array_merge($result, $this->getAllId());
        $result = array_merge($result, $this->getAllPersistent());
        
        ksort($result);
                
        return $result;
    }


    public function setAll(array $data)
    {   
        $data = array_merge(
        [
            'types' => [ ], 
            'subject_identifiers' => [ ], 
            'subject_locators' => [ ], 
            'names' => [ ], 
            'occurrences' => [ ],
            'reifies_what' => iTopic::REIFIES_NONE,
            'reifies_id' => ''
        ], $data);
        
        $this->setAllId($data);

        $this->setAllPersistent($data);
        
        $this->setTypeIds($data[ 'types' ]);

        $this->setSubjectIdentifiers($data[ 'subject_identifiers' ]);

        $this->setSubjectLocators($data[ 'subject_locators' ]);
        
        $this->setNames([ ]);
        
        foreach ($data[ 'names' ] as $name_data)
        {
            $name = $this->newName();
            $name->setAll($name_data);
        }
        
        $this->setOccurrences([ ]);
        
        foreach ($data[ 'occurrences' ] as $occurrence_data)
        {
            $occurrence = $this->newOccurrence();
            $occurrence->setAll($occurrence_data);
        }
        
        $this->setReifiesWhat($data[ 'reifies_what' ]);
        $this->setReifiesId($data[ 'reifies_id' ]);
        
        return 1;
    }
    

    // XXX to be implemented: if this topic is a reifier, empty
    // the reifier property in the reifying object on delete    
}
