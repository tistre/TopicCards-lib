<?php

namespace TopicCards\Interfaces;


interface CoreInterface
{
    public function __construct(TopicMapInterface $topicmap);
    
    /**
     * @return TopicMapInterface
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
    
    public function setAll(array $data);
}
