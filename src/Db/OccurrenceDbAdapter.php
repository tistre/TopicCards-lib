<?php

namespace TopicCards\Db;

use GraphAware\Neo4j\Client\Exception\Neo4jException;
use GraphAware\Neo4j\Client\Transaction\Transaction;
use TopicCards\Interfaces\iOccurrence;
use TopicCards\Interfaces\iOccurrenceDbAdapter;
use TopicCards\Interfaces\iTopicMap;


class OccurrenceDbAdapter implements iOccurrenceDbAdapter
{
    /** @var iOccurrence */
    protected $occurrence;

    /** @var iTopicMap */
    protected $topicmap;


    public function __construct(iOccurrence $occurrence)
    {
        $this->occurrence = $occurrence;
        $this->topicmap = $occurrence->getTopicMap();
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

        $query = 'MATCH (t:Topic { id: {id} })-[:hasOccurrence]->(node:Occurrence) RETURN node';
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
                    'datatype' => ($node->hasValue('datatype') ? $node->value('datatype') : false),
                    'scope' => [ ],
                    'reifier' => ($node->hasValue('reifier') ? $node->value('reifier') : false)
                ];

            // Type

            $types = array_values(array_diff($node->labels(), [ 'Occurrence' ]));
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
        foreach ($data as $occurrence_data)
        {
            $this->insertOccurrence($topic_id, $occurrence_data, $transaction);
        }

        // TODO: error handling

        return 1;
    }
    
    
    public function updateAll($topic_id, array $data, array $previous_data, Transaction $transaction)
    {
        $ok = 0;
        
        foreach ($data as $occurrence_data)
        {
            // No ID? Must be a new occurrence

            if (empty($occurrence_data[ 'id' ]))
            {
                $ok = $this->insertOccurrence($topic_id, $occurrence_data, $transaction);

                if ($ok < 0)
                {
                    return $ok;
                }

                continue;
            }

            // If the ID is not in $previous_data, it's a new occurrence

            $found = false;

            foreach ($previous_data as $previous_occurrence_data)
            {
                if ($previous_occurrence_data[ 'id' ] === $occurrence_data[ 'id' ])
                {
                    $found = true;
                    break;
                }
            }

            if (! $found)
            {
                $ok = $this->insertOccurrence($topic_id, $occurrence_data, $transaction);

                if ($ok < 0)
                {
                    return $ok;
                }

                continue;
            }

            // It's an updated occurrence...

            $ok = $this->updateOccurrence($topic_id, $occurrence_data, $previous_occurrence_data, $transaction);

            if ($ok < 0)
            {
                return $ok;
            }

            // TODO: handle occurrence deletion, or empty value
        }

        // TODO: error handling
        return $ok;
    }
    
    
    protected function insertOccurrence($topic_id, array $data, Transaction $transaction)
    {
        $logger = $this->topicmap->getLogger();
        
        if ((! isset($data[ 'value' ])) || (strlen($data[ 'value' ]) === 0))
        {
            return 0;
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

        if (! isset($data[ 'reifier' ]))
        {
            $data[ 'reifier' ] = false;
        }

        $property_data =
            [
                'id' => $data[ 'id' ],
                'value' => $data[ 'value' ],
                'datatype' => $data[ 'datatype' ],
                'scope' => $data[ 'scope' ],
                'reifier' => $data[ 'reifier' ]
            ];

        $bind = [ 'topic_id' => $topic_id ];

        $property_query = DbUtils::propertiesString($property_data, $bind);

        $classes = [ 'Occurrence' , $data[ 'type' ] ];

        $query = sprintf
        (
            'MATCH (a:Topic { id: {topic_id} })'
            . ' CREATE (a)-[:hasOccurrence]->(b%s { %s })',
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
            iTopicMap::SUBJECT_OCCURRENCE_TYPE
        );

        $type_queries = array_merge($type_queries, DbUtils::tmConstructLabelQueries
        (
            $this->topicmap,
            $data[ 'scope' ],
            iTopicMap::SUBJECT_SCOPE
        ));

        $type_queries = array_merge($type_queries, DbUtils::tmConstructLabelQueries
        (
            $this->topicmap,
            [ $data[ 'datatype' ] ],
            iTopicMap::SUBJECT_DATATYPE
        ));

        foreach ($type_queries as $type_query)
        {
            $logger->info($type_query['query'], $type_query['bind']);
            $transaction->push($type_query['query'], $type_query['bind']);
        }

        // TODO: error handling
        return 1;
    }


    protected function updateOccurrence($topic_id, array $data, array $previous_data, Transaction $transaction)
    {
        $logger = $this->topicmap->getLogger();
        
        if ((! isset($data[ 'value' ])) || (strlen($data[ 'value' ]) === 0))
        {
            $bind = [ 'id' => $data[ 'id' ] ];
            $query = 'MATCH (node:Occurrence { id: {id} }) OPTIONAL MATCH (node)-[r:hasOccurrence]-() DELETE r, node';

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

        foreach ([ 'value', 'datatype', 'scope', 'reifier' ] as $key)
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
                    iTopicMap::SUBJECT_SCOPE
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
            'MATCH (node:Occurrence { id: {id} })%s',
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
                iTopicMap::SUBJECT_OCCURRENCE_TYPE
            );

            foreach ($type_queries as $type_query)
            {
                $logger->info($type_query['query'], $type_query['bind']);
                $transaction->push($type_query['query'], $type_query['bind']);
            }

            $dirty = true;
        }

        if (! $dirty)
        {
            return 0;
        }

        $logger->info($query, $bind);

        $transaction->push($query, $bind);

        // TODO: error handling
        return 1;
    }
}
