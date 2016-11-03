<?php

namespace TopicCards\Interfaces;


interface OccurrenceInterface extends CoreInterface, ReifiedInterface, ScopedInterface, TypedInterface
{
    public function getValue();
    public function setValue($str);
    public function getDatatypeId();
    public function setDatatypeId($topic_id);
    public function getDatatype();
    public function setDatatype($topic_subject);
}
