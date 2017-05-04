<?php

namespace TopicCards\Interfaces;


interface NameInterface extends CoreInterface, ReifiedInterface, ScopedInterface, TypedInterface
{
    public function getValue();
    public function setValue($str);
    public function getDataTypeId();
    public function setDataTypeId($topic_id);
    public function getDataType();
    public function setDataType($topicSubject);
    public function getLanguage();
    public function setLanguage($language);
}
