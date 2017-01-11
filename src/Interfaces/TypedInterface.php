<?php

namespace TopicCards\Interfaces;


interface TypedInterface
{
    const ERR_TYPE_MISSING = -21;


    public function getTypeId();


    public function setTypeId($topic_id);


    public function getType();


    public function setType($topic_subject);


    public function hasTypeId($topic_id);


    public function hasType($topic_subject);
}
