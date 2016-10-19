<?php

namespace TopicCards\Interfaces;


interface iTyped
{
    public function getTypeId();
    public function setTypeId($topic_id);
    public function getType();
    public function setType($topic_subject);
    public function hasTypeId($topic_id);
    public function hasType($topic_subject);
}
