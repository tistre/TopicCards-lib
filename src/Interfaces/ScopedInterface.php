<?php

namespace TopicCards\Interfaces;


interface ScopedInterface
{
    public function getScopeIds();


    public function setScopeIds(array $topicIds);


    public function getScope();


    public function setScope(array $topicSubjects);


    public function matchesScope(array $matchTopicIds);
}
