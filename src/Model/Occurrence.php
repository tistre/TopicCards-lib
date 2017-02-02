<?php

namespace TopicCards\Model;

use TopicCards\Db\OccurrenceDbAdapter;
use TopicCards\Interfaces\OccurrenceInterface;
use TopicCards\Interfaces\OccurrenceDbAdapterInterface;
use TopicCards\Interfaces\TopicMapInterface;
use TopicCards\Utils\DataTypeUtils;


class Occurrence extends Core implements OccurrenceInterface
{
    use ReifiedTrait, ScopedTrait, TypedTrait;

    protected $value = false;
    protected $dataType = false;
    protected $language = false;

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


    public function getDataTypeId()
    {
        return $this->dataType;
    }


    public function setDataTypeId($topic_id)
    {
        $this->dataType = $topic_id;

        return 1;
    }


    public function getDataType()
    {
        return $this->topicMap->getTopicSubject($this->getDataTypeId());
    }


    public function setDataType($topicSubject)
    {
        $topicId = $this->topicMap->getTopicIdBySubject($topicSubject, true);

        if (strlen($topicId) === 0) {
            return -1;
        }

        return $this->setDataTypeId($topicId);
    }


    public function getLanguage()
    {
        return $this->language;
    }


    public function setLanguage($language)
    {
        $this->language = $language;
    }


    public function validate(&$msgHtml)
    {
        $ok = DataTypeUtils::validate
        (
            $this->value,
            $this->getDataType(),
            $msgTxt
        );

        $msgHtml = htmlspecialchars($msgTxt);

        return $ok;
    }


    public function getAll()
    {
        $result =
            [
                'value' => $this->getValue(),
                'datatype' => $this->getDataTypeId(),
                'language' => $this->getLanguage()
            ];

        $result = array_merge($result, $this->getAllId());

        $result = array_merge($result, $this->getAllTyped());

        $result = array_merge($result, $this->getAllReified());

        $result = array_merge($result, $this->getAllScoped());

        return $result;
    }


    public function setAll(array $data)
    {
        $data = array_merge(
            [
                'value' => false,
                'datatype' => false,
                'language' => false
            ], $data);

        $ok = $this->setValue($data['value']);

        if ($ok >= 0) {
            $ok = $this->setDataTypeId($data['datatype']);
        }

        if ($ok >= 0) {
            $ok = $this->setLanguage($data['language']);
        }

        if ($ok >= 0) {
            $ok = $this->setAllId($data);
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
