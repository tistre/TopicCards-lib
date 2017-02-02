<?php

namespace TopicCards\Model;


trait TypedTrait
{
    protected $type = false;


    public function getTypeId()
    {
        return $this->type;
    }


    public function setTypeId($topicId)
    {
        $this->type = $topicId;

        return 1;
    }


    public function getType()
    {
        return $this->topicMap->getTopicSubject($this->type);
    }


    public function setType($topicSubject)
    {
        $topicId = $this->topicMap->getTopicIdBySubject($topicSubject, true);

        if (strlen($topicId) === 0) {
            return -1;
        }

        return $this->setTypeId($topicId);
    }


    public function hasTypeId($topicId)
    {
        return ($this->getTypeId() === $topicId);
    }


    public function hasType($topicSubject)
    {
        return ($this->getType() === $topicSubject);
    }


    public function getAllTyped()
    {
        return
            [
                'type' => $this->getTypeId()
            ];
    }


    public function setAllTyped(array $data)
    {
        $data = array_merge(
            [
                'type' => false
            ], $data);

        return $this->setTypeId($data['type']);
    }
}
