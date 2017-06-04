<?php

namespace TopicCards\Model;


trait ScopedTrait
{
    /** @var string[] */
    protected $scope = [];


    /**
     * @return string[]
     */
    public function getScopeIds()
    {
        return $this->scope;
    }


    /**
     * @param string[] $topicIds
     * @return self
     */
    public function setScopeIds(array $topicIds)
    {
        $this->scope = $topicIds;

        return $this;
    }


    /**
     * @return string[]
     */
    public function getScope()
    {
        $result = [];

        foreach ($this->getScopeIds() as $topicId) {
            $result[] = $this->topicMap->getTopicSubject($topicId);
        }

        return $result;
    }


    /**
     * @param string[] $topicSubjects
     * @return self
     */
    public function setScope(array $topicSubjects)
    {
        $topicIds = [];
        $ok = 1;

        foreach ($topicSubjects as $topicSubject) {
            $topicId = $this->topicMap->getTopicIdBySubject($topicSubject, true);

            if (strlen($topicId) === 0) {
                // TODO Add error handling
                $ok = -1;
            } else {
                $topicIds[] = $topicId;
            }
        }

        $this->setScopeIds($topicIds);

        return $this;
    }


    /**
     * @return array
     */
    public function getAllScoped()
    {
        return
            [
                'scope' => $this->getScopeIds()
            ];
    }


    /**
     * @param array $data
     * @return self
     */
    public function setAllScoped(array $data)
    {
        $data = array_merge(
            [
                'scope' => []
            ], $data);

        $this->setScopeIds($data['scope']);
        
        return $this;
    }


    /**
     * @param string[] $matchTopicIds
     * @return bool
     */
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
