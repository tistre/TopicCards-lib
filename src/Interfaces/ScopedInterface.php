<?php

namespace TopicCards\Interfaces;


interface ScopedInterface
{
    /**
     * @return string[]
     */
    public function getScopeIds();


    /**
     * @param string[] $topicIds
     * @return self
     */
    public function setScopeIds(array $topicIds);


    /**
     * @return string[]
     */
    public function getScope();


    /**
     * @param string[] $topicSubjects
     * @return self
     */
    public function setScope(array $topicSubjects);


    /**
     * @param string[] $matchTopicIds
     * @return bool
     */
    public function matchesScope(array $matchTopicIds);
}
