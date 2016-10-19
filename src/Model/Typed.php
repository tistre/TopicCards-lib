<?php

namespace TopicCards\Model;


trait Typed
{
    // TODO
    // use TypedDbAdapter;
    
    protected $type = false;
    
    
    public function getTypeId()
    {
        return $this->type;
    }
    

    public function setTypeId($topic_id)
    {
        $this->type = $topic_id;
        return 1;
    }


    public function getType()
    {
        return $this->topicmap->getTopicSubject($this->type);
    }

    
    public function setType($topic_subject)
    {
        $topic_id = $this->topicmap->getTopicIdBySubject($topic_subject, true);
        
        if (strlen($topic_id) === 0)
        {
            return -1;
        }
            
        return $this->setTypeId($topic_id);
    }


    public function hasTypeId($topic_id)
    {
        return ($this->getTypeId() === $topic_id);
    }
    
    
    public function hasType($topic_subject)
    {
        return ($this->getType() === $topic_subject);
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
        
        return $this->setTypeId($data[ 'type' ]);
    }
}
