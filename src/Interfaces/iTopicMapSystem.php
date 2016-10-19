<?php

namespace TopicCards\Interfaces;


interface iTopicMapSystem
{
    public function newTopicMap($key);
    public function getTopicMap($key);
    public function hasTopicMap($key);
    public function getTopicMapKeys();
}
