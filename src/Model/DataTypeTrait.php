<?php

namespace TopicCards\Model;

use TopicCards\Utils\DataTypeUtils;


trait DataTypeTrait
{
    /** @var string */
    protected $dataType = '';


    /**
     * @return string
     */
    public function getDataTypeId()
    {
        $this->setDefaultDataType();
        
        return $this->dataType;
    }


    /**
     * @param string $topic_id
     * @return self
     */
    public function setDataTypeId($topic_id)
    {
        $this->dataType = $topic_id;
        
        $this->setDefaultDataType();

        return $this;
    }


    /**
     * @return string
     */
    public function getDataType()
    {
        return $this->topicMap->getTopicSubject($this->getDataTypeId());
    }


    /**
     * @param string $topicSubject
     * @return self
     */
    public function setDataType($topicSubject)
    {
        $topicId = $this->topicMap->getTopicIdBySubject($topicSubject, true);

        if (strlen($topicId) > 0) {
            $this->setDataTypeId($topicId);
        }

        return $this;
    }


    /**
     * @param string $msgHtml
     * @return int
     */
    public function validateDataType(&$msgHtml)
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


    /**
     * @return array
     */
    public function getAllDataType()
    {
        return
            [
                'datatype' => $this->getDataTypeId()
            ];
    }


    /**
     * @param array $data
     * @return int
     */
    public function setAllDataType(array $data)
    {
        $data = array_merge(
            [
                'datatype' => false
            ], $data);

        $this->setDataTypeId($data['datatype']);
        
        return 1;
    }


    /**
     * @return void
     */
    protected function setDefaultDataType()
    {
        if (strlen($this->dataType) === 0) {
            $this->dataType = $this->topicMap->getTopicIdBySubject(DataTypeUtils::DATATYPE_STRING);
        }
    }
}
