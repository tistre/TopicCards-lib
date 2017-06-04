<?php

namespace TopicCards\Search;

use TopicCards\Interfaces\PersistentSearchAdapterInterface;
use TopicCards\Interfaces\TopicMapInterface;


abstract class PersistentSearchAdapter implements PersistentSearchAdapterInterface
{
    /** @var TopicMapInterface */
    protected $topicMap;

    /** @var array|bool */
    protected $indexRelated = false;


    /**
     * @return string
     */
    abstract public function getSearchType();


    /**
     * @return string
     */
    abstract protected function getId();


    /**
     * @return array
     */
    abstract protected function getIndexFields();


    /**
     * @return int
     */
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


    /**
     * @return int
     */
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


    /**
     * @param array $params
     * @return array|bool
     */
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


    /**
     * @return void
     */
    public function resetIndexRelated()
    {
        $this->indexRelated = ['topic_id' => [], 'association_id' => []];
    }


    /**
     * @param mixed $add
     * @return int
     */
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


    /**
     * @return int
     */
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
