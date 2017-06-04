<?php

namespace TopicCards\Interfaces;


interface CoreInterface
{
    /**
     * CoreInterface constructor.
     *
     * @param TopicMapInterface $topicMap
     */
    public function __construct(TopicMapInterface $topicMap);
    

    /**
     * @return TopicMapInterface
     */
    public function getTopicMap();


    /**
     * @return string
     */
    public function getId();


    /**
     * @param string $id
     * @return self
     */
    public function setId($id);


    /**
     * @param string $msgHtml
     * @return int
     */
    public function validate(&$msgHtml);


    /**
     * @return array
     */
    public function getAll();


    /**
     * @param array $data
     * @return self
     */
    public function setAll(array $data);
}
