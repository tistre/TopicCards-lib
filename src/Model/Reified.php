<?php

namespace TopicCards\Model;

use TopicCards\Interfaces\AssociationInterface;
use TopicCards\Interfaces\NameInterface;
use TopicCards\Interfaces\OccurrenceInterface;
use TopicCards\Interfaces\RoleInterface;
use TopicCards\Interfaces\TopicInterface;
use TopicCards\Interfaces\TopicMapInterface;


trait Reified
{
    protected $reifier = false;


    public function getReifierId()
    {
        return $this->reifier;
    }


    public function setReifierId($topicId)
    {
        $this->reifier = $topicId;

        return 1;
    }


    public function getAllReified()
    {
        return
            [
                'reifier' => $this->getReifierId()
            ];
    }


    public function setAllReified(array $data)
    {
        $data = array_merge(
            [
                'reifier' => false
            ], $data);

        return $this->setReifierId($data['reifier']);
    }


    /**
     * @return TopicInterface
     */
    public function newReifierTopic()
    {
        /** @var TopicMapInterface $topicMap */
        $topicMap = $this->getTopicMap();

        // We need this object to have an ID so that
        // we can store it in the reifier topic

        if (strlen($this->getId()) === 0) {
            $this->setId($topicMap->createId());
        }

        $reifierId = $topicMap->createId();

        $reifierTopic = $topicMap->newTopic();
        $reifierTopic->setId($reifierId);

        $this->setReifierId($reifierId);

        return $reifierTopic;
    }
}
