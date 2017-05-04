<?php

namespace TopicCards\Model;

use TopicCards\Db\OccurrenceDbAdapter;
use TopicCards\Interfaces\OccurrenceInterface;
use TopicCards\Interfaces\OccurrenceDbAdapterInterface;
use TopicCards\Interfaces\TopicMapInterface;


class Occurrence extends Core implements OccurrenceInterface
{
    use DataTypeTrait, LanguageTrait, ReifiedTrait, ScopedTrait, TypedTrait;

    protected $value = false;

    /** @var OccurrenceDbAdapterInterface */
    protected $dbAdapter;


    /**
     * Name constructor.
     *
     * @param TopicMapInterface $topicMap
     */
    public function __construct(TopicMapInterface $topicMap)
    {
        parent::__construct($topicMap);

        $this->dbAdapter = new OccurrenceDbAdapter($this);
    }


    /**
     * @return OccurrenceDbAdapterInterface
     */
    public function getDbAdapter()
    {
        return $this->dbAdapter;
    }


    public function getValue()
    {
        return $this->value;
    }


    public function setValue($str)
    {
        $this->value = $str;

        return 1;
    }


    public function validate(&$msgHtml)
    {
        return $this->validateDataType($msgHtml);
    }


    public function getAll()
    {
        $result =
            [
                'value' => $this->getValue()
            ];

        $result = array_merge($result, $this->getAllId());

        $result = array_merge($result, $this->getAllLanguage());

        $result = array_merge($result, $this->getAllDataType());
        
        $result = array_merge($result, $this->getAllTyped());

        $result = array_merge($result, $this->getAllReified());

        $result = array_merge($result, $this->getAllScoped());

        return $result;
    }


    public function setAll(array $data)
    {
        $data = array_merge(
            [
                'value' => false
            ], $data);

        $ok = $this->setValue($data['value']);

        if ($ok >= 0) {
            $ok = $this->setAllId($data);
        }

        if ($ok >= 0) {
            $ok = $this->setAllLanguage($data);
        }

        if ($ok >= 0) {
            $ok = $this->setAllDataType($data);
        }

        if ($ok >= 0) {
            $ok = $this->setAllTyped($data);
        }

        if ($ok >= 0) {
            $ok = $this->setAllReified($data);
        }

        if ($ok >= 0) {
            $ok = $this->setAllScoped($data);
        }

        return $ok;
    }


    /**
     * Mark an existing (saved) occurrence for removal on topic save
     */
    public function remove()
    {
        $this->setValue('');
    }
}
