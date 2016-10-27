<?php

namespace TopicCards\Model;

use TopicCards\Interfaces\iAssociation;
use TopicCards\Interfaces\iName;
use TopicCards\Interfaces\iOccurrence;
use TopicCards\Interfaces\iRole;
use TopicCards\Interfaces\iTopic;
use TopicCards\Interfaces\iTopicMap;


trait Reified
{
    protected $reifier = false;
    
    
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
        /** @var iTopicMap $topicmap */
        $topicmap = $this->getTopicMap();
        
        // We need this object to have an ID so that
        // we can store it in the reifier topic
        
        if (strlen($this->getId()) === 0)
        {
            $this->setId($topicmap->createId());
        }
        
        $reifier_id = $topicmap->createId();
        
        if ($this instanceof iName)
        {
            $what = iTopic::REIFIES_NAME;
        }
        elseif ($this instanceof iOccurrence)
        {
            $what = iTopic::REIFIES_OCCURRENCE;
        }
        elseif ($this instanceof iAssociation)
        {
            $what = iTopic::REIFIES_ASSOCIATION;
        }
        elseif ($this instanceof iRole)
        {
            $what = iTopic::REIFIES_ROLE;
        }
        else
        {
            $what = iTopic::REIFIES_NONE;
        }
        
        $reifier_topic = $topicmap->newTopic();
        $reifier_topic->setId($reifier_id);
        $reifier_topic->setReifiesId($this->getId());
        $reifier_topic->setReifiesWhat($what);
                
        $this->setReifierId($reifier_id);
        
        return $reifier_topic;
    }
}
