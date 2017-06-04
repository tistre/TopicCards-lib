<?php

namespace TopicCards\Search;

use TopicCards\Interfaces\TopicInterface;


// TODO add an interface
class TopicSearchAdapter extends PersistentSearchAdapter
{
    /** @var TopicInterface */
    protected $topic;


    /**
     * TopicSearchAdapter constructor.
     *
     * @param TopicInterface $topic
     */
    public function __construct(TopicInterface $topic)
    {
        $this->topic = $topic;
        $this->topicMap = $topic->getTopicMap();
    }


    /**
     * @return string
     */
    public function getSearchType()
    {
        return 'topic';
    }


    /**
     * @return string
     */
    protected function getId()
    {
        return $this->topic->getId();
    }


    /**
     * @return array
     */
    protected function getIndexFields()
    {
        $result =
            [
                // XXX add sort date
                'label' => $this->topic->getLabel(),
                'name' => [],
                'has_name_type_id' => [],
                'topic_type_id' => $this->topic->getTypeIds(),
                'subject' => array_merge($this->topic->getSubjectIdentifiers(), $this->topic->getSubjectLocators()),
                'occurrence' => [],
                'has_occurrence_type_id' => []
            ];

        foreach ($this->topic->getNames([]) as $name) {
            $result['name'][] = $name->getValue();
            $result['has_name_type_id'][] = $name->getTypeId();
        }

        foreach ($this->topic->getOccurrences([]) as $occurrence) {
            $result['occurrence'][] = $occurrence->getValue();
            $result['has_occurrence_type_id'][] = $occurrence->getTypeId();
        }

        $callbackResult = [];

        $this->topicMap->trigger
        (
            TopicInterface::EVENT_INDEXING,
            ['topic' => $this->topic, 'index_fields' => $result],
            $callbackResult
        );

        if (isset($callbackResult['index_fields']) && is_array($callbackResult['index_fields'])) {
            $result = $callbackResult['index_fields'];
        }

        return $result;
    }
}
