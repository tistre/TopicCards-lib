<?php

namespace TopicCards\Interfaces;


interface TypedInterface
{
    const ERR_TYPE_MISSING = -21;


    /**
     * @return string
     */
    public function getTypeId();


    /**
     * @param string $topicId
     * @return self
     */
    public function setTypeId($topicId);


    /**
     * @return string
     */
    public function getType();


    /**
     * @param string $topicSubject
     * @return self
     */
    public function setType($topicSubject);


    /**
     * @param string $topicId
     * @return bool
     */
    public function hasTypeId($topicId);


    /**
     * @param string $topicSubject
     * @return bool
     */
    public function hasType($topicSubject);
}
