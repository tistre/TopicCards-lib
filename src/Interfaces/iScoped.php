<?php

namespace TopicCards\Interfaces;


interface iScoped
{
    public function getScopeIds();
    public function setScopeIds(array $topic_ids);
    public function getScope();
    public function setScope(array $topic_subjects);
    public function matchesScope(array $match_topic_ids);
}
