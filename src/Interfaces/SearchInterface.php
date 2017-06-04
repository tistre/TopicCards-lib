<?php

namespace TopicCards\Interfaces;

use Elasticsearch\Client;


interface SearchInterface
{
    const EVENT_INDEX_PARAMS = 'search_index_params';


    /**
     * SearchInterface constructor.
     *
     * @param array $params
     */
    public function __construct(array $params);

    // TODO add setLogger(), getLogger()


    /**
     * @return array
     */
    public function getParams();


    /**
     * @return Client
     */
    public function getConnection();


    /**
     * @param array $params
     * @return array|bool
     */
    public function search(array $params);


    /**
     * @param array $params
     * @return array|bool
     */
    public function index(array $params);


    /**
     * @param array $params
     * @return array|bool
     */
    public function get(array $params);


    /**
     * @param array $params
     * @return array|bool
     */
    public function delete(array $params);


    /**
     * @param string $method
     * @param array $params
     * @return array|bool
     */
    public function run($method, array $params);


    /**
     * @return string
     */
    public function getIndexName();


    /**
     * @param TopicMapInterface $topicMap
     * @param string $index
     * @return array
     */
    public function getIndexParams(TopicMapInterface $topicMap, $index);


    /**
     * @param TopicMapInterface $topicMap
     * @param string $index
     * @param array $params
     * @return int
     */
    public function recreateIndex(TopicMapInterface $topicMap, $index, array $params);


    /**
     * @param TopicMapInterface $topicMap
     * @return int
     */
    public function reindexAllTopics(TopicMapInterface $topicMap);


    /**
     * @param TopicMapInterface $topicMap
     * @return int
     */
    public function reindexAllAssociations(TopicMapInterface $topicMap);
}
