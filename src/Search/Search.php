<?php

namespace TopicCards\Search;

use Elasticsearch\Client;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use TopicCards\Interfaces\iSearch;
use TopicCards\Interfaces\iTopicMap;


class Search implements iSearch
{
    const EVENT_INDEX_PARAMS = 'search_index_params';

    /** @var Client */
    protected $connection;


    public function __construct(array $params)
    {
        $this->params = $params;
    }


    /**
     * @return Client
     */
    public function getConnection()
    {
        if (! $this->connection)
        {
            if (! isset($this->params[ 'connection' ]))
            {
                $this->params[ 'connection' ] = [ ];
            }
            
            $this->connection = new Client($this->params[ 'connection' ]);
        }

        return $this->connection;
    }
    
    
    public function search(array $params)
    {
        return $this->run('search', $params);
    }
    
    
    public function index(array $params)
    {
        return $this->run('index', $params);
    }
    
    
    public function get(array $params)
    {
        return $this->run('get', $params);
    }
    
    
    public function delete(array $params)
    {
        return $this->run('delete', $params);
    }


    public function run($method, array $params)
    {
        $this->getConnection();
        
        if (! isset($params[ 'index' ]))
        {
            $params[ 'index' ] = $this->params[ 'index' ];
        }
        
        try
        {
            $result = $this->connection->$method($params);
        }
        catch (\Exception $e)
        {
            // Delete on a non-indexed item returns a 404Exception, ignore that
            if ($e instanceof Missing404Exception)
            {
                $result = true;
            }
            else
            {
                trigger_error(sprintf("%s %s: %s", __METHOD__, $method, $e->getMessage()), E_USER_WARNING);
                $result = false;
            }
        }
        
        return $result;
    }
    
    
    public function getIndexParams(iTopicMap $topicmap, $index)
    {
        $params =
            [
                'index' => $index,
                'body' =>
                    [
                        'mappings' =>
                            [
                                'topic' =>
                                    [
                                        '_source' => [ 'enabled' => true ],
                                        'properties' =>
                                            [
                                                'has_name_type_id' =>
                                                    [
                                                        'type' => 'string',
                                                        'index' => 'not_analyzed'
                                                    ],
                                                'has_occurrence_type_id' =>
                                                    [
                                                        'type' => 'string',
                                                        'index' => 'not_analyzed'
                                                    ],
                                                'label' =>
                                                    [
                                                        'type' => 'string',
                                                        'fields' =>
                                                            [
                                                                'raw' =>
                                                                    [
                                                                        'type' => 'string',
                                                                        'index' => 'not_analyzed'
                                                                    ]
                                                            ]
                                                    ],
                                                'name' =>
                                                    [
                                                        'type' => 'string'
                                                    ],
                                                'occurrence' =>
                                                    [
                                                        'type' => 'string'
                                                    ],
                                                'subject' =>
                                                    [
                                                        'type' => 'string',
                                                        'index' => 'not_analyzed'
                                                    ],
                                                'topic_type_id' =>
                                                    [
                                                        'type' => 'string',
                                                        'index' => 'not_analyzed'
                                                    ]
                                            ]
                                    ],
                                'association' =>
                                    [
                                        '_source' => [ 'enabled' => true ],
                                        'properties' =>
                                            [
                                                'association_type_id' =>
                                                    [
                                                        'type' => 'string',
                                                        'index' => 'not_analyzed'
                                                    ],
                                                'has_player_id' =>
                                                    [
                                                        'type' => 'string',
                                                        'index' => 'not_analyzed'
                                                    ],
                                                'has_role_type_id' =>
                                                    [
                                                        'type' => 'string',
                                                        'index' => 'not_analyzed'
                                                    ]
                                            ]
                                    ],
                                'history' =>
                                    [
                                        '_source' => [ 'enabled' => true ],
                                        'properties' =>
                                            [
                                                'dml' =>
                                                    [
                                                        'type' => 'string',
                                                        'index' => 'not_analyzed'
                                                    ],
                                                'id' =>
                                                    [
                                                        'type' => 'string',
                                                        'index' => 'not_analyzed'
                                                    ],
                                                'type' =>
                                                    [
                                                        'type' => 'string',
                                                        'index' => 'not_analyzed'
                                                    ],
                                                'user_id' =>
                                                    [
                                                        'type' => 'string',
                                                        'index' => 'not_analyzed'
                                                    ],
                                                'when' =>
                                                    [
                                                        'type' => 'date'
                                                    ]
                                            ]
                                    ]
                            ]
                    ]
            ];

        $callback_result = [ ];

        $topicmap->trigger
        (
            self::EVENT_INDEX_PARAMS,
            [ 'index_params' => $params ],
            $callback_result
        );

        if (isset($callback_result[ 'index_params' ]) && is_array($callback_result[ 'index_params' ]))
            $params = $callback_result[ 'index_params' ];

        return $params;
    }


    public function recreateIndex($index, array $params)
    {
        $connection = $this->getConnection();

        if (strlen($index) === 0)
        {
            $index = $this->params[ 'index' ];
        }

        if ($connection->indices()->exists([ 'index' => $index ]))
        {
            $connection->indices()->delete([ 'index' => $index ]);
        }

        $connection->indices()->create($params);

        return 1;
    }


    public function reindexAllTopics(iTopicMap $topicmap)
    {
        $limit = 0;
        $topic_ids = $topicmap->getTopicIds([ 'limit' => $limit ]);

        $topic = $topicmap->newTopic();
        $cnt = 0;

        foreach ($topic_ids as $topic_id)
        {
            $ok = $topic->load($topic_id);

            if ($ok >= 0)
                $ok = $topic->getSearchAdapter()->index();

            printf("#%d %s (%s)\n", ++$cnt, $topic->getId(), $ok);

            if (($limit > 0) && ($cnt >= $limit))
                break;
        }
        
        return $cnt;
    }


    public function reindexAllAssociations(iTopicMap $topicmap)
    {
        $limit = 0;
        $association_ids = $topicmap->getAssociationIds([ 'limit' => $limit ]);

        $association = $topicmap->newAssociation();
        $cnt = 0;

        foreach ($association_ids as $association_id)
        {
            $ok = $association->load($association_id);

            if ($ok >= 0)
                $association->getSearchAdapter()->index();

            if (($limit > 0) && ($cnt >= $limit))
                break;
        }
        
        return $cnt;
    }
}
