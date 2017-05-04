<?php

namespace TopicCards\Model;

use TopicCards\Utils\DataTypeUtils;


trait DataTypeTrait
{
    protected $dataType = false;


    public function getDataTypeId()
    {
        $this->setDefaultDataType();
        
        return $this->dataType;
    }


    public function setDataTypeId($topic_id)
    {
        $this->dataType = $topic_id;
        
        $this->setDefaultDataType();

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


    public function getAllDataType()
    {
        return
            [
                'datatype' => $this->getDataTypeId()
            ];
    }


    public function setAllDataType(array $data)
    {
        $data = array_merge(
            [
                'datatype' => false
            ], $data);

        return $this->setDataTypeId($data['datatype']);
    }


    protected function setDefaultDataType()
    {
        if (strlen($this->dataType) === 0) {
            $this->dataType = $this->topicMap->getTopicIdBySubject(DataTypeUtils::DATATYPE_STRING);
        }
    }
}
