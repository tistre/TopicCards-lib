<?php

namespace TopicCards\Search;

use TopicCards\Interfaces\iTopic;
use TopicCards\Interfaces\iTopicMap;

// TODO add an interface
class TopicSearchAdapter extends PersistentSearchAdapter
{
    /** @var iTopic */
    protected $topic;


    public function __construct(iTopic $topic)
    {
        $this->topic = $topic;
        $this->topicmap = $topic->getTopicMap();
    }

    
    public function getSearchType()
    {
        return 'topic';
    }


    protected function getId()
    {
        return $this->topic->getId();
    }
    
    
    protected function getIndexFields()
    {
        $result = 
        [ 
            // XXX add sort date
            'label' => $this->topic->getLabel(),
            'name' => [ ],
            'has_name_type_id' => [ ],
            'topic_type_id' => $this->topic->getTypeIds(),
            'subject' => array_merge($this->topic->getSubjectIdentifiers(), $this->topic->getSubjectLocators()),
            'occurrence' => [ ],
            'has_occurrence_type_id' => [ ]
        ];
        
        foreach ($this->topic->getNames([ ]) as $name)
        {
            $result[ 'name' ][ ] = $name->getValue();
            $result[ 'has_name_type_id' ][ ] = $name->getTypeId();
        }

        foreach ($this->topic->getOccurrences([ ]) as $occurrence)
        {
            $result[ 'occurrence' ][ ] = $occurrence->getValue();
            $result[ 'has_occurrence_type_id' ][ ] = $occurrence->getTypeId();
        }

        $callback_result = [ ];

        $this->topicmap->trigger
        (
            iTopic::EVENT_INDEXING, 
            [ 'topic' => $this, 'index_fields' => $result ],
            $callback_result
        );
        
        if (isset($callback_result[ 'index_fields' ]) && is_array($callback_result[ 'index_fields' ]))
            $result = $callback_result[ 'index_fields' ];
        
        return $result;
    }
}
