<?php

namespace TopicCards\Interfaces;


interface iCore
{
    public function __construct(iTopicMap $topicmap);
    
    /**
     * @return iTopicMap
     */
    
    public function getTopicMap();
    
    /**
     * @return string
     */
    public function getId();
    
    public function setId($id);
    public function validate(&$msg_html);
    
    /**
     * @return array
     */
    public function getAll();
}
