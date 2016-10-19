<?php

namespace TopicCards\Model;

use TopicCards\Interfaces\iTopic;


trait Reified
{
    protected $reifier;
    
    
    public function getReifierId()
    {
        return $this->reifier;
    }
    
    
    public function setReifierId($topic_id)
    {
        $this->reifier = $topic_id;
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
        
        return $this->setReifierId($data[ 'reifier' ]);
    }


    /**
     * @return iTopic
     */
    public function newReifierTopic()
    {
        $reifier_id = $this->topicmap->createId();
        
        $reifier_topic = $this->topicmap->newTopic();
        $reifier_topic->setId($reifier_id);
                
        $this->setReifierId($reifier_id);
        
        return $reifier_topic;
    }
}
