<?php

namespace TopicCards\Search;

use TopicCards\Interfaces\PersistentSearchAdapterInterface;
use TopicCards\Interfaces\TopicMapInterface;


abstract class PersistentSearchAdapter implements PersistentSearchAdapterInterface
{
    /** @var TopicMapInterface */
    protected $topicMap;

    protected $indexRelated = false;


    abstract public function getSearchType();


    abstract protected function getId();


    abstract protected function getIndexFields();


    public function index()
    {
        $response = $this->topicMap->getSearch()->index
        (
            [
                'type' => $this->getSearchType(),
                'id' => $this->getId(),
                'body' => $this->getIndexFields()
            ]
        );

        if ($response === false) {
            return -1;
        }

        return 1;
    }


    public function removeFromIndex()
    {
        $response = $this->topicMap->getSearch()->delete
        (
            [
                'type' => $this->getSearchType(),
                'id' => $this->getId()
            ]
        );

        if ($response === false) {
            return -1;
        }

        return 1;
    }


    public function getIndexedData()
    {
        return $this->topicMap->getSearch()->get
        (
            [
                'type' => $this->getSearchType(),
                'id' => $this->getId()
            ]
        );
    }


    public function resetIndexRelated()
    {
        $this->indexRelated = ['topic_id' => [], 'association_id' => []];
    }


    public function addIndexRelated($add)
    {
        if (! is_array($this->indexRelated)) {
            $this->resetIndexRelated();
        }

        if (! is_array($add)) {
            return 0;
        }

        foreach (['topic_id', 'association_id'] as $key) {
            if (isset($add[$key]) && is_array($add[$key])) {
                $this->indexRelated[$key] = array_merge
                (
                    $this->indexRelated[$key],
                    $add[$key]
                );
            }
        }

        return 1;
    }


    public function indexRelated()
    {
        // TODO to be implemented
        $cnt = 0;

        if (count($this->indexRelated['topic_id']) > 0) {
            $topic = $this->getTopicMap()->newTopic();

            $topicIds = array_unique($this->indexRelated['topic_id']);

            foreach ($topicIds as $topicId) {
                $topic->load($topicId);
                $topic->index();

                $cnt++;
            }
        }

        /* TODO implement associations
        if (count($this->indexRelated[ 'association_id' ]) > 0)
        {
            $association = $this->getTopicMap()->newAssociation();
            
            $associationIds = array_unique($this->indexRelated[ 'association_id' ]);

            foreach ($associationIds as $associationId)
            {
                $association->load($associationId);
                $association->index();
                
                $cnt++;
            }
        }
        */

        return $cnt;
    }
}
