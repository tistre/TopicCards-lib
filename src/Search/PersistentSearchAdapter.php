<?php

namespace TopicCards\Search;

use TopicCards\Interfaces\iPersistentSearchAdapter;
use TopicCards\Interfaces\iTopicMap;


abstract class PersistentSearchAdapter implements iPersistentSearchAdapter
{
    /** @var iTopicMap */
    protected $topicmap;

    protected $index_related = false;


    abstract public function getSearchType();


    abstract protected function getId();


    abstract protected function getIndexFields();
    
    
    public function index()
    {
        $response = $this->topicmap->getSearch()->index
        (
            [
                'type' => $this->getSearchType(),
                'id' => $this->getId(),
                'body' => $this->getIndexFields()
            ]
        );

        if ($response === false)
        {
            return -1;
        }
        
        return 1;
    }
    
    
    public function removeFromIndex()
    {
        $response = $this->topicmap->getSearch()->delete
        (
            [
                'type' => $this->getSearchType(),
                'id' => $this->getId()
            ]
        );

        if ($response === false)
        {
            return -1;
        }
        
        return 1;
    }

    
    public function getIndexedData()
    {
        return $this->topicmap->getSearch()->get
        (
            [
                'type' => $this->getSearchType(),
                'id' => $this->getId()
            ]
        );
    }    


    public function resetIndexRelated()
    {
        $this->index_related = [ 'topic_id' => [ ], 'association_id' => [ ] ];
    }
    
    
    public function addIndexRelated($add)
    {
        if (! is_array($this->index_related))
            $this->resetIndexRelated();

        if (! is_array($add))
            return 0;

        foreach ([ 'topic_id', 'association_id' ] as $key)
        {
            if (isset($add[ $key ]) && is_array($add[ $key ]))
            {
                $this->index_related[ $key ] = array_merge
                (
                    $this->index_related[ $key ],
                    $add[ $key ]
                );
            }
        }

        return 1;
    }
    
    
    public function indexRelated()
    {
        // TODO to be implemented
        $cnt = 0;

        if (count($this->index_related[ 'topic_id' ]) > 0)
        {
            $topic = $this->getTopicMap()->newTopic();

            $topic_ids = array_unique($this->index_related[ 'topic_id' ]);

            foreach ($topic_ids as $topic_id)
            {
                $topic->load($topic_id);
                $topic->index();

                $cnt++;
            }
        }
        /* TODO implement associations
        if (count($this->index_related[ 'association_id' ]) > 0)
        {
            $association = $this->getTopicMap()->newAssociation();
            
            $association_ids = array_unique($this->index_related[ 'association_id' ]);

            foreach ($association_ids as $association_id)
            {
                $association->load($association_id);
                $association->index();
                
                $cnt++;
            }
        }
        */

        return $cnt;
    }
}
