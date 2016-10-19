<?php

namespace TopicCards\Model;

use TopicCards\Interfaces\iTopicMapSystem;


class TopicMapSystem implements iTopicMapSystem
{
    protected $topicmaps = [ ];
    
    
    public function newTopicMap($key)
    {
        $topicmap = new TopicMap();
        
        $this->topicmaps[ $key ] = $topicmap;
        
        return $topicmap;
    }
    

    public function getTopicMap($key)
    {
        if (! $this->hasTopicMap($key))
            return false;
            
        return $this->topicmaps[ $key ];
    }
    
    
    public function hasTopicMap($key)
    {
        return isset($this->topicmaps[ $key ]);
    }
    
    
    public function getTopicMapKeys()
    {
        return array_keys($this->topicmaps);
    }
}
