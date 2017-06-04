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


    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }


    /**
     * @param string $str
     * @return self
     */
    public function setValue($str)
    {
        $this->value = $str;

        return $this;
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


    /**
     * @param array $data
     * @return self
     */
    public function setAll(array $data)
    {
        $data = array_merge(
            [
                'value' => false
            ], $data);

        $this->setValue($data['value']);
        $this->setAllId($data);
        $this->setAllLanguage($data);
        $this->setAllDataType($data);
        $this->setAllTyped($data);
        $this->setAllReified($data);
        $this->setAllScoped($data);

        return $this;
    }


    /**
     * Mark an existing (saved) occurrence for removal on topic save
     */
    public function remove()
    {
        $this->setValue('');
    }
}
