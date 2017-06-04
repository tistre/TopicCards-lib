<?php

namespace TopicCards\Model;


trait TypedTrait
{
    /** @var string */
    protected $type = '';


    /**
     * @return string
     */
    public function getTypeId()
    {
        return $this->type;
    }


    /**
     * @param string $topicId
     * @return self
     */
    public function setTypeId($topicId)
    {
        $this->type = $topicId;

        return $this;
    }


    /**
     * @return string
     */
    public function getType()
    {
        return $this->topicMap->getTopicSubject($this->type);
    }


    /**
     * @param string $topicSubject
     * @return self
     */
    public function setType($topicSubject)
    {
        $topicId = $this->topicMap->getTopicIdBySubject($topicSubject, true);

        if (strlen($topicId) > 0) {
            $this->setTypeId($topicId);
        }

        return $this;
    }


    /**
     * @param string $topicId
     * @return bool
     */
    public function hasTypeId($topicId)
    {
        return ($this->getTypeId() === $topicId);
    }


    /**
     * @param string $topicSubject
     * @return bool
     */
    public function hasType($topicSubject)
    {
        return ($this->getType() === $topicSubject);
    }


    /**
     * @return array
     */
    public function getAllTyped()
    {
        return
            [
                'type' => $this->getTypeId()
            ];
    }


    /**
     * @param array $data
     * @return self
     */
    public function setAllTyped(array $data)
    {
        $data = array_merge(
            [
                'type' => false
            ], $data);

        $this->setTypeId($data['type']);
        
        return $this;
    }
}
