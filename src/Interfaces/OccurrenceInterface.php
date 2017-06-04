<?php

namespace TopicCards\Interfaces;


interface OccurrenceInterface extends CoreInterface, ReifiedInterface, ScopedInterface, TypedInterface
{
    /**
     * @return string
     */
    public function getValue();


    /**
     * @param string $str
     * @return self
     */
    public function setValue($str);


    /**
     * @return string
     */
    public function getDataTypeId();


    /**
     * @param string $topic_id
     * @return self
     */
    public function setDataTypeId($topic_id);


    /**
     * @return string
     */
    public function getDataType();


    /**
     * @param string $topicSubject
     * @return self
     */
    public function setDataType($topicSubject);


    /**
     * @return string
     */
    public function getLanguage();


    /**
     * @param string $language
     * @return self
     */
    public function setLanguage($language);
}
