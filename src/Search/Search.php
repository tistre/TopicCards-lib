<?php

namespace TopicCards\Search;

use Elasticsearch\Client;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use TopicCards\Interfaces\SearchInterface;
use TopicCards\Interfaces\TopicMapInterface;


class Search implements SearchInterface
{
    /** @var array */
    protected $params = [];

    /** @var Client */
    protected $connection;


    /**
     * Search constructor.
     *
     * @param array $params
     */
    public function __construct(array $params)
    {
        $this->params = $params;
    }


    /**
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }


    /**
     * @return Client
     */
    public function getConnection()
    {
        if (! $this->connection) {
            if (! isset($this->params['connection'])) {
                $this->params['connection'] = [];
            }

            $this->connection = new Client($this->params['connection']);
        }

        return $this->connection;
    }


    /**
     * @param array $params
     * @return array|bool
     */
    public function search(array $params)
    {
        return $this->run('search', $params);
    }


    /**
     * @param array $params
     * @return array|bool
     */
    public function index(array $params)
    {
        return $this->run('index', $params);
    }


    /**
     * @param array $params
     * @return array|bool
     */
    public function get(array $params)
    {
        return $this->run('get', $params);
    }


    /**
     * @param array $params
     * @return array|bool
     */
    public function delete(array $params)
    {
        return $this->run('delete', $params);
    }


    /**
     * @param string $method
     * @param array $params
     * @return array|bool
     */
    public function run($method, array $params)
    {
        $this->getConnection();

        if (! isset($params['index'])) {
            $params['index'] = $this->params['index'];
        }

        try {
            $result = $this->connection->$method($params);
        } catch (\Exception $e) {
            // Delete on a non-indexed item returns a 404Exception, ignore that
            if ($e instanceof Missing404Exception) {
                $result = true;
            } else {
                trigger_error(sprintf("%s %s: %s", __METHOD__, $method, $e->getMessage()), E_USER_WARNING);
                $result = false;
            }
        }

        return $result;
    }


    /**
     * @return string
     */
    public function getIndexName()
    {
        return $this->params['index'];
    }


    /**
     * @param TopicMapInterface $topicMap
     * @param string $index
     * @return array
     */
    public function getIndexParams(TopicMapInterface $topicMap, $index)
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
                                        '_source' => ['enabled' => true],
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
                                        '_source' => ['enabled' => true],
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
                                        '_source' => ['enabled' => true],
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

        $callbackResult = [];

        $topicMap->trigger
        (
            SearchInterface::EVENT_INDEX_PARAMS,
            ['index_params' => $params],
            $callbackResult
        );

        if (isset($callbackResult['index_params']) && is_array($callbackResult['index_params'])) {
            $params = $callbackResult['index_params'];
        }

        return $params;
    }


    /**
     * @param TopicMapInterface $topicMap
     * @param string $index
     * @param array $params
     * @return int
     */
    public function recreateIndex(TopicMapInterface $topicMap, $index, array $params)
    {
        $connection = $this->getConnection();

        if (strlen($index) === 0) {
            $index = $this->params['index'];
        }

        if ($connection->indices()->exists(['index' => $index])) {
            $connection->indices()->delete(['index' => $index]);
        }

        $connection->indices()->create($params);

        return 1;
    }


    /**
     * @param TopicMapInterface $topicMap
     * @return int
     */
    public function reindexAllTopics(TopicMapInterface $topicMap)
    {
        $limit = 0;
        $topicIds = $topicMap->getTopicIds(['limit' => $limit]);

        $topic = $topicMap->newTopic();
        $cnt = 0;

        foreach ($topicIds as $topicId) {
            $ok = $topic->load($topicId);

            if ($ok >= 0) {
                $ok = $topic->getSearchAdapter()->index();
            }

            printf("#%d %s (%s)\n", ++$cnt, $topic->getId(), $ok);

            if (($limit > 0) && ($cnt >= $limit)) {
                break;
            }
        }

        return $cnt;
    }


    /**
     * @param TopicMapInterface $topicMap
     * @return int
     */
    public function reindexAllAssociations(TopicMapInterface $topicMap)
    {
        $limit = 0;
        $associationIds = $topicMap->getAssociationIds(['limit' => $limit]);

        $association = $topicMap->newAssociation();
        $cnt = 0;

        foreach ($associationIds as $associationId) {
            $ok = $association->load($associationId);

            if ($ok >= 0) {
                $association->getSearchAdapter()->index();
            }

            if (($limit > 0) && ($cnt >= $limit)) {
                break;
            }
        }

        return $cnt;
    }
}
