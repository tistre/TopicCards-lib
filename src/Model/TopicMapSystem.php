<?php

namespace TopicCards\Model;

use TopicCards\Interfaces\TopicMapSystemInterface;


class TopicMapSystem implements TopicMapSystemInterface
{
    protected $topicMaps = [];


    public function newTopicMap($key)
    {
        $topicMap = new TopicMap();
        $topicMap->setId($key);

        $this->topicMaps[$key] = $topicMap;

        return $topicMap;
    }


    public function getTopicMap($key)
    {
        if (! $this->hasTopicMap($key)) {
            return false;
        }

        return $this->topicMaps[$key];
    }


    public function hasTopicMap($key)
    {
        return isset($this->topicMaps[$key]);
    }


    public function getTopicMapKeys()
    {
        return array_keys($this->topicMaps);
    }
}
