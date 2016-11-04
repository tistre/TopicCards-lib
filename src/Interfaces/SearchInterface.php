<?php

namespace TopicCards\Interfaces;

use Elasticsearch\Client;


interface SearchInterface
{
    const EVENT_INDEX_PARAMS = 'search_index_params';
    
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


    public function search(array $params);


    public function index(array $params);


    public function get(array $params);


    public function delete(array $params);


    public function run($method, array $params);


    /**
     * @return string
     */
    public function getIndexName();


    public function getIndexParams(TopicMapInterface $topicmap, $index);


    public function recreateIndex(TopicMapInterface $topicmap, $index, array $params);


    public function reindexAllTopics(TopicMapInterface $topicmap);


    public function reindexAllAssociations(TopicMapInterface $topicmap);
}
