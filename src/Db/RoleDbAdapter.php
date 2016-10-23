<?php

namespace TopicCards\Db;

use GraphAware\Neo4j\Client\Exception\Neo4jException;
use GraphAware\Neo4j\Client\Transaction\Transaction;
use TopicCards\Interfaces\iRole;
use TopicCards\Interfaces\iRoleDbAdapter;
use TopicCards\Interfaces\iTopicMap;


class RoleDbAdapter implements iRoleDbAdapter
{
    /** @var iRole */
    protected $role;

    /** @var iTopicMap */
    protected $topicmap;


    public function __construct(iRole $role)
    {
        $this->role = $role;
        $this->topicmap = $role->getTopicMap();
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

        if (! isset($filters[ 'association' ]))
        {
            return -1;
        }

        $query = 'MATCH (assoc:Association { id: {id} })-[rel]-(node:Topic) RETURN assoc, rel, node';
        $bind = [ 'id' => $filters[ 'association' ] ];

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
            $rel = $record->get('rel');
            // TODO: Only fetch the topic ID, not the whole topic
            $node = $record->get('node');
            // TODO: Only fetch the association ID, not the whole association
            $assoc = $record->get('assoc');

            $row =
                [
                    'id' => ($rel->hasValue('id') ? $rel->value('id') : false),
                    'association' => ($assoc->hasValue('id') ? $assoc->value('id') : false),
                    'player' => ($node->hasValue('id') ? $node->value('id') : false)
                ];

            // Type

            $row[ 'type' ] = $rel->type();

            $result[ ] = $row;
        }

        return $result;
    }


    public function insertAll($association_id, array $data, Transaction $transaction)
    {
        foreach ($data as $role_data)
        {
            $this->insertRole($association_id, $role_data, $transaction);
        }

        // TODO: error handling

        return 1;
    }
    
    
    public function updateAll($association_id, array $data, array $previous_data, Transaction $transaction)
    {
        $ok = 1;
        
        foreach ($data as $role_data)
        {
            // No ID? Must be a new role

            if (empty($role_data[ 'id' ]))
            {
                $ok = $this->insertRole($association_id, $role_data, $transaction);

                if ($ok < 0)
                {
                    return $ok;
                }

                continue;
            }

            // If the ID is not in $previous_data, it's a new role

            $found = false;
            $previous_role_data = [ ];

            foreach ($previous_data as $previous_role_data)
            {
                if ($previous_role_data[ 'id' ] === $role_data[ 'id' ])
                {
                    $found = true;
                    break;
                }
            }

            if (! $found)
            {
                $ok = $this->insertRole($association_id, $role_data, $transaction);

                if ($ok < 0)
                {
                    return $ok;
                }

                continue;
            }

            // It's an updated role...

            $ok = $this->updateRole($association_id, $role_data, $previous_role_data, $transaction);

            if ($ok < 0)
            {
                return $ok;
            }

            // TODO: handle role deletion, or empty value
        }

        // TODO: error handling
        return $ok;
    }
    
    
    protected function insertRole($association_id, array $data, Transaction $transaction)
    {
        $logger = $this->topicmap->getLogger();
        
        if ((! isset($data[ 'player' ])) || (strlen($data[ 'player' ]) === 0))
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

        $property_data =
            [
                'id' => $data[ 'id' ]
            ];

        $bind = 
            [ 
                'association_id' => $association_id,
                'topic_id' => $data[ 'player' ]
            ];

        $property_query = DbUtils::propertiesString($property_data, $bind);

        $classes = [ $data[ 'type' ] ];

        $query = sprintf
        (
            'MATCH (a:Association), (t:Topic)'
            . ' WHERE a.id = {association_id} AND t.id = {topic_id}'
            . ' CREATE (a)-[r%s { %s }]->(t)',
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
            iTopicMap::SUBJECT_ASSOCIATION_ROLE_TYPE
        );

        foreach ($type_queries as $type_query)
        {
            $logger->info($type_query['query'], $type_query['bind']);
            $transaction->push($type_query['query'], $type_query['bind']);
        }
        
        // TODO: error handling
        return 1;
    }


    protected function updateRole($association_id, array $data, array $previous_data, Transaction $transaction)
    {
        $logger = $this->topicmap->getLogger();
        
        $do_delete = $do_insert = false;
        $ok = 0;
        
        if ((! isset($data[ 'player' ])) || (strlen($data[ 'player' ]) === 0))
        {
            $do_delete = true;
        }
        elseif (($previous_data[ 'player' ] !== $data[ 'player' ]) || ($previous_data[ 'type' ] !== $data[ 'type' ]))
        {
            $do_delete = $do_insert = true;
        }
        
        if ($do_delete)
        {
            $bind = 
                [ 
                    'id' => $data[ 'id' ],
                    'association_id' => $association_id,
                    'player_id' => $previous_data[ 'player' ]
                ];
            
            $query = 'MATCH (a:Association { id: {association_id} })-[r { id: {id} }]-(t:Topic { id: {player_id} }) DELETE r';

            $logger->info($query, $bind);
            
            $transaction->push($query, $bind);

            // TODO: error handling
            $ok = 1;
        }

        if ($do_insert)
        {
            $ok = $this->insertRole($association_id, $data, $transaction);
        }

        return $ok;
    }
}
