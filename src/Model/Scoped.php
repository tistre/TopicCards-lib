<?php

namespace TopicCards\Model;


trait Scoped
{
    protected $scope = [];


    public function getScopeIds()
    {
        return $this->scope;
    }


    public function setScopeIds(array $topic_ids)
    {
        $this->scope = $topic_ids;

        return 1;
    }


    public function getScope()
    {
        $result = [];

        foreach ($this->getScopeIds() as $topic_id) {
            $result[] = $this->topicmap->getTopicSubject($topic_id);
        }

        return $result;
    }


    public function setScope(array $topic_subjects)
    {
        $topic_ids = [];
        $result = 1;

        foreach ($topic_subjects as $topic_subject) {
            $topic_id = $this->topicmap->getTopicIdBySubject($topic_subject, true);

            if (strlen($topic_id) === 0) {
                $result = -1;
            } else {
                $topic_ids[] = $topic_id;
            }
        }

        $ok = $this->setScopeIds($topic_ids);

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


    public function matchesScope(array $match_topic_ids)
    {
        $my_topic_ids = $this->getScopeIds();

        $my_count = count($my_topic_ids);
        $match_count = count($match_topic_ids);

        // Short cut: If counts differ, scopes cannot match

        if ($my_count !== $match_count) {
            return false;
        }

        // Exact match, independent of order

        return
            (
                (count(array_diff($my_topic_ids, $match_topic_ids)) === 0)
                && (count(array_diff($match_topic_ids, $my_topic_ids)) === 0)
            );
    }
}
