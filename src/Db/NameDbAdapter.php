<?php

namespace TopicCards\Db;

use GraphAware\Neo4j\Client\Exception\Neo4jException;
use GraphAware\Neo4j\Client\Transaction\Transaction;
use TopicCards\Interfaces\NameInterface;
use TopicCards\Interfaces\NameDbAdapterInterface;
use TopicCards\Interfaces\TopicInterface;
use TopicCards\Interfaces\TopicMapInterface;


class NameDbAdapter implements NameDbAdapterInterface
{
    /** @var NameInterface */
    protected $name;

    /** @var TopicMapInterface */
    protected $topicmap;


    public function __construct(NameInterface $name)
    {
        $this->name = $name;
        $this->topicmap = $name->getTopicMap();
    }


    public function selectAll(array $filters)
    {
        $logger = $this->topicmap->getLogger();
        $db = $this->topicmap->getDb();

        $db_conn = $db->getConnection();

        if ($db_conn === NULL)
        {
            return -1;
        }
        
        if (! empty($filters[ 'reifier' ]))
        {
            // TODO to be implemented
            return -1;
        }

        if (! isset($filters[ 'topic' ]))
        {
            return -1;
        }
        
        $query = 'MATCH (t:Topic { id: {id} })-[:hasName]->(node:Name) RETURN node';
        $bind = [ 'id' => $filters[ 'topic' ] ];

        $logger->info($query, $bind);
        
        try
        {
            $qresult = $db_conn->run($query, $bind);
        }
        catch (Neo4jException $exception)
        {
            $logger->error($exception->getMessage());
            // TODO: Error handling
            return -1;
        }

        $result = [ ];
        
        foreach ($qresult->records() as $record)
        {
            $node = $record->get('node');
            
            $row =
                [
                    'id' => ($node->hasValue('id') ? $node->value('id') : false),
                    'value' => ($node->hasValue('value') ? $node->value('value') : false),
                    'scope' => [ ],
                    'reifier' => ($node->hasValue('reifier') ? $node->value('reifier') : false),
                ];
            
            // Type

            $types = array_values(array_diff($node->labels(), [ 'Name' ]));
            $row[ 'type' ] = $types[ 0 ];

            // Scope

            if ($node->hasValue('scope'))
            {
                $row[ 'scope' ] = $node->value('scope');

                if (! is_array($row[ 'scope' ]))
                {
                    $value = $row[ 'scope' ];
                    $row[ 'scope' ] = [ ];

                    if (strlen($value) > 0)
                    {
                        $row[ 'scope' ][ ] = $value;
                    }
                }
            }
            
            $result[ ] = $row;
        }
        
        return $result;
    }


    public function insertAll($topic_id, array $data, Transaction $transaction)
    {
        foreach ($data as $name_data)
        {
            $this->insertName($topic_id, $name_data, $transaction);
        }

        // TODO: error handling
        
        return 1;
    }
    
    
    public function updateAll($topic_id, array $data, array $previous_data, Transaction $transaction)
    {
        $ok = 1;
        $previous_name_data = [ ];
        
        foreach ($data as $name_data)
        {
            // No ID? Must be a new name
            
            if (empty($name_data[ 'id' ]))
            {
                $ok = $this->insertName($topic_id, $name_data, $transaction);
                
                if ($ok < 0)
                {
                    return $ok;
                }
                
                continue;
            }
            
            // If the ID is not in $previous_data, it's a new name
            
            $found = false;
            
            foreach ($previous_data as $previous_name_data)
            {
                if ($previous_name_data[ 'id' ] === $name_data[ 'id' ])
                {
                    $found = true;
                    break;
                }
            }
            
            if (! $found)
            {
                $ok = $this->insertName($topic_id, $name_data, $transaction);
                
                if ($ok < 0)
                {
                    return $ok;
                }
                
                continue;
            }

            // It's an updated name...

            $ok = $this->updateName($topic_id, $name_data, $previous_name_data, $transaction);
            
            if ($ok < 0)
            {
                return $ok;
            }
            
            // TODO: handle name deletion, or empty value
        }
        
        // TODO: error handling
        return $ok;
    }


    protected function insertName($topic_id, array $data, Transaction $transaction)
    {
        $logger = $this->topicmap->getLogger();
        
        if ((! isset($data[ 'value' ])) || (strlen($data[ 'value' ]) === 0))
        {
            return 0;
        }

        if (empty($data[ 'type' ]))
        {
            return -1;
        }

        if (empty($data[ 'id' ]))
        {
            $data[ 'id' ] = $this->topicmap->createId();
        }

        if (empty($data[ 'scope' ]))
        {
            $data[ 'scope' ] = [ ];
        }
        elseif (! is_array($data[ 'scope' ]))
        {
            $data[ 'scope' ] = [ $data[ 'scope' ] ];
        }

        if (empty($data[ 'reifier' ]))
        {
            $data[ 'reifier' ] = false;
        }

        $property_data =
            [
                'id' => $data[ 'id' ],
                'value' => $data[ 'value' ],
                'scope' => $data[ 'scope' ],
                'reifier' => $data[ 'reifier' ]
            ];

        $bind = [ 'topic_id' => $topic_id ];

        $property_query = DbUtils::propertiesString($property_data, $bind);

        $classes = [ 'Name' , $data[ 'type' ] ];

        $query = sprintf
        (
            'MATCH (a:Topic { id: {topic_id} })'
            . ' CREATE (a)-[:hasName]->(b%s { %s })',
            DbUtils::labelsString($classes),
            $property_query
        );
        
        $logger->info($query, $bind);

        $transaction->push($query, $bind);

        // Mark type topics

        $type_queries = DbUtils::tmConstructLabelQueries
        (
            $this->topicmap,
            [ $data[ 'type' ] ],
            TopicMapInterface::SUBJECT_TOPIC_NAME_TYPE
        );

        $type_queries = array_merge($type_queries, DbUtils::tmConstructLabelQueries
        (
            $this->topicmap,
            $data[ 'scope' ],
            TopicMapInterface::SUBJECT_SCOPE
        ));

        foreach ($type_queries as $type_query)
        {
            $logger->info($type_query['query'], $type_query['bind']);
            $transaction->push($type_query['query'], $type_query['bind']);
        }

        // Link reifier

        if (strlen($data[ 'reifier' ]) > 0)
        {
            $reifier_queries = DbUtils::tmConstructLinkReifierQueries
            (
                TopicInterface::REIFIES_NAME,
                $data[ 'id' ],
                $data[ 'reifier' ]
            );

            foreach ($reifier_queries as $query)
            {
                $logger->info($query['query'], $query['bind']);
                $transaction->push($query['query'], $query['bind']);
            }
        }

        // TODO: error handling
        return 1;
    }
    
    
    protected function updateName($topic_id, array $data, array $previous_data, Transaction $transaction)
    {
        $logger = $this->topicmap->getLogger();
        
        if ((! isset($data[ 'value' ])) || (strlen($data[ 'value' ]) === 0))
        {
            $bind = [ 'id' => $data[ 'id' ] ];
            $query = 'MATCH (node:Name { id: {id} }) OPTIONAL MATCH (node)-[r:hasName]-() DELETE r, node';

            $logger->info($query, $bind);
            
            $transaction->push($query, $bind);

            // TODO: error handling
            return 1;
        }

        if (empty($data[ 'scope' ]))
        {
            $data[ 'scope' ] = [ ];
        }
        elseif (! is_array($data[ 'scope' ]))
        {
            $data[ 'scope' ] = [ $data[ 'scope' ] ];
        }

        if (! isset($data[ 'reifier' ]))
        {
            $data[ 'reifier' ] = false;
        }

        $property_data = [ ];

        foreach ([ 'value', 'scope', 'reifier' ] as $key)
        {
            // Skip unmodified values

            if (isset($previous_data[ $key ]) && (serialize($previous_data[ $key ]) === serialize($data[ $key ])))
            {
                continue;
            }

            $property_data[ $key ] = $data[ $key ];
            
            if ($key === 'scope')
            {
                // Mark type topics

                $type_queries = DbUtils::tmConstructLabelQueries
                (
                    $this->topicmap,
                    $data[ $key ],
                    TopicMapInterface::SUBJECT_SCOPE
                );

                foreach ($type_queries as $type_query)
                {
                    $logger->info($type_query['query'], $type_query['bind']);
                    $transaction->push($type_query['query'], $type_query['bind']);
                }
            }
        }
        
        $bind = [ 'id' => $data[ 'id' ] ];
        $property_query = DbUtils::propertiesUpdateString('node', $property_data, $bind);

        // Skip update if no property changes and no type change!
        $dirty = (strlen($property_query) > 0);

        $query = sprintf
        (
            'MATCH (node:Name { id: {id} })%s',
            $property_query
        );

        if ($data[ 'type' ] !== $previous_data[ 'type' ])
        {
            $query .= sprintf
            (
                ' REMOVE node%s',
                DbUtils::labelsString([ $previous_data[ 'type' ] ])
            );

            $query .= sprintf
            (
                ' SET node%s',
                DbUtils::labelsString([ $data[ 'type' ] ])
            );

            // Mark type topics

            $type_queries = DbUtils::tmConstructLabelQueries
            (
                $this->topicmap,
                [ $data[ 'type' ] ],
                TopicMapInterface::SUBJECT_TOPIC_NAME_TYPE
            );

            foreach ($type_queries as $type_query)
            {
                $logger->info($type_query['query'], $type_query['bind']);
                $transaction->push($type_query['query'], $type_query['bind']);
            }

            $dirty = true;
        }

        if ($dirty)
        {
            $logger->info($query, $bind);
            $transaction->push($query, $bind);
        }

        // Link reifier

        if ($data[ 'reifier' ] !== $previous_data[ 'reifier' ])
        {
            if (strlen($data['reifier']) > 0)
            {
                $reifier_queries = DbUtils::tmConstructLinkReifierQueries
                (
                    TopicInterface::REIFIES_NAME,
                    $data['id'],
                    $data['reifier']
                );
            }
            else
            {
                $reifier_queries = DbUtils::tmConstructUnlinkReifierQueries
                (
                    TopicInterface::REIFIES_NAME,
                    $data['id'],
                    $previous_data['reifier']
                );
            }

            foreach ($reifier_queries as $query)
            {
                $logger->info($query['query'], $query['bind']);
                $transaction->push($query['query'], $query['bind']);
            }
        }


        // TODO: error handling
        return 1;
    }
}
