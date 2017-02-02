<?php

namespace TopicCards\Model;


trait ScopedTrait
{
    protected $scope = [];


    public function getScopeIds()
    {
        return $this->scope;
    }


    public function setScopeIds(array $topicIds)
    {
        $this->scope = $topicIds;

        return 1;
    }


    public function getScope()
    {
        $result = [];

        foreach ($this->getScopeIds() as $topicId) {
            $result[] = $this->topicMap->getTopicSubject($topicId);
        }

        return $result;
    }


    public function setScope(array $topicSubjects)
    {
        $topicIds = [];
        $result = 1;

        foreach ($topicSubjects as $topicSubject) {
            $topicId = $this->topicMap->getTopicIdBySubject($topicSubject, true);

            if (strlen($topicId) === 0) {
                $result = -1;
            } else {
                $topicIds[] = $topicId;
            }
        }

        $ok = $this->setScopeIds($topicIds);

        if ($ok < 0) {
            $result = $ok;
        }

        return $result;
    }


    public function getAllScoped()
    {
        return
            [
                'scope' => $this->getScopeIds()
            ];
    }


    public function setAllScoped(array $data)
    {
        $data = array_merge(
            [
                'scope' => []
            ], $data);

        return $this->setScopeIds($data['scope']);
    }


    public function matchesScope(array $matchTopicIds)
    {
        $myTopicIds = $this->getScopeIds();

        $myCount = count($myTopicIds);
        $matchCount = count($matchTopicIds);

        // Short cut: If counts differ, scopes cannot match

        if ($myCount !== $matchCount) {
            return false;
        }

        // Exact match, independent of order

        return
            (
                (count(array_diff($myTopicIds, $matchTopicIds)) === 0)
                && (count(array_diff($matchTopicIds, $myTopicIds)) === 0)
            );
    }
}
