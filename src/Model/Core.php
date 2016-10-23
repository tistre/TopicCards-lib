<?php

namespace TopicCards\Model;

use TopicCards\Interfaces\iCore;
use TopicCards\Interfaces\iTopicMap;


abstract class Core implements iCore
{
    /** @var iTopicMap */
    protected $topicmap;

    /** @var string */
    protected $id = '';


    /**
     * Core constructor.
     *
     * @param iTopicMap $topicmap
     */
    public function __construct(iTopicMap $topicmap)
    {
        $this->topicmap = $topicmap;
    }


    /**
     * @return iTopicMap
     */
    public function getTopicMap()
    {
        return $this->topicmap;
    }

    
    /**
     * @param string $msg_html
     * @return int
     */
    public function validate(&$msg_html)
    {
        $msg_html = '';
        
        return 0;
    }


    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }


    /**
     * @param string $id
     * @return int
     */
    public function setId($id)
    {
        $this->id = $id;
        return 1;
    }


    /**
     * @return array
     */
    public function getAllId()
    {
        return
            [
                'id' => $this->getId()
            ];
    }


    /**
     * @param array $data
     * @return int
     */
    public function setAllId(array $data)
    {
        $data = array_merge(
            [
                'id' => false
            ], $data);

        $this->setId($data[ 'id' ]);

        return 1;
    }


    /**
     * @return array
     */
    abstract public function getAll();
}
