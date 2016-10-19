<?php

namespace TopicCards\Interfaces;


interface iCore
{
    public function __construct(iTopicMap $topicmap);
    public function getTopicMap();
    public function getId();
    public function setId($id);
    public function validate(&$msg_html);
}
