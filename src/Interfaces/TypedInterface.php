<?php

namespace TopicCards\Interfaces;


interface TypedInterface
{
    const ERR_TYPE_MISSING = -21;


    public function getTypeId();


    public function setTypeId($topicId);


    public function getType();


    public function setType($topicSubject);


    public function hasTypeId($topicId);


    public function hasType($topicSubject);
}
