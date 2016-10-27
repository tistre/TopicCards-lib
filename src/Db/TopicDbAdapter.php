<?php

namespace TopicCards\Db;

use GraphAware\Neo4j\Client\Exception\Neo4jException;
use TopicCards\Interfaces\iTopic;
use TopicCards\Interfaces\iTopicDbAdapter;
use TopicCards\Interfaces\iTopicMap;
use TopicCards\Model\Association;
use TopicCards\Model\Name;
use TopicCards\Model\Occurrence;
use TopicCards\Model\Role;
use TopicCards\Model\Topic;


class TopicDbAdapter implements iTopicDbAdapter
{
    /** @var iTopic */
    protected $topic;
    
    /** @var iTopicMap */
    protected $topicmap;


    public function __construct(iTopic $topic)
    {
        $this->topic = $topic;
        $this->topicmap = $topic->getTopicMap();
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

        $query = 'MATCH (node:Topic { id: {id} }) RETURN node';
        $bind = [ 'id' => $filters[ 'id' ] ];

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

        // TODO add error handling

        $result = [ ];
        
        $name = new Name($this->topicmap);
        $occurrence = new Occurrence($this->topicmap);

        foreach ($qresult->records() as $record)
        {
            $node = $record->get('node');
            
            $row =
                [
                    'created' => $node->value('created'),
                    'id' => $node->value('id'),
                    'updated' => $node->value('updated'),
                    'version' => $node->value('version'),
                    'types' => array_values(array_diff($node->labels(), [ 'Topic' ])),
                    'subject_identifiers' => [ ],
                    'subject_locators' => [ ],
                    'reifies_what' => ($node->hasValue('reifies_what') ? $node->value('reifies_what') : iTopic::REIFIES_NONE),
                    'reifies_id' => ($node->hasValue('reifies_id') ? $node->value('reifies_id') : '')
                ];

            // Subjects
            
            foreach ([ 'subject_identifiers', 'subject_locators' ] as $key)
            {
                if (! $node->hasValue($key))
                {
                    continue;
                }
                
                $values = $node->value($key);
                
                if (! is_array($values))
                {
                    $value = $values;
                    $values = [ ];
                    
                    if (strlen($value) > 0)
                    {
                        $values[ ] = $value;
                    }
                }
                
                $row[ $key ] = $values;
            }

            $row[ 'names' ] = $name->getDbAdapter()->selectAll([ 'topic' => $row[ 'id' ] ]);

            $row[ 'occurrences' ] = $occurrence->getDbAdapter()->selectAll([ 'topic' => $row[ 'id' ] ]);

            $result[ ] = $row;
        }

        return $result;        
    }
    

    public function selectReifiedObject()
    {
        $reifies_what = $this->topic->getReifiesWhat();
        
        $map =
        [
            iTopic::REIFIES_NAME => 'Name',
            iTopic::REIFIES_OCCURRENCE => 'Occurrence',
            iTopic::REIFIES_ASSOCIATION => 'Association',
            iTopic::REIFIES_ROLE => 'Role'
        ];
        
        if (! isset($map[ $reifies_what ]))
            return false;
        
        $method = 'selectReifiedObject_' . $map[ $reifies_what ];
        
        return $this->$method();
    }
    
    
    protected function selectReifiedObject_Name()
    {
        $logger = $this->topicmap->getLogger();
        $db = $this->topicmap->getDb();

        $db_conn = $db->getConnection();

        if ($db_conn === NULL)
        {
            return false;
        }

        $query = 
            'MATCH (t:Topic)-[:hasName]->(n:Name { reifier: {reifier_id} })' 
            . ' RETURN t.id AS topic_id, n.id AS name_id';
        
        $bind = [ 'reifier_id' => $this->topic->getId() ];

        $logger->info($query, $bind);

        try
        {
            $qresult = $db_conn->run($query, $bind);
        }
        catch (Neo4jException $exception)
        {
            $logger->error($exception->getMessage());
            return false;
        }

        foreach ($qresult->records() as $record)
        {
            $topic = new Topic($this->topicmap);
            $ok = $topic->load($record->get('topic_id'));

            if ($ok < 0)
            {
                return false;
            }

            foreach ($topic->getNames([ 'id' => $record->get('name_id') ]) as $name)
            {
                return
                    [
                        'topic' => $topic,
                        'name' => $name
                    ];
            }
        }
        
        return false;
    }
    
    
    protected function selectReifiedObject_Occurrence()
    {
        $logger = $this->topicmap->getLogger();
        $db = $this->topicmap->getDb();

        $db_conn = $db->getConnection();

        if ($db_conn === NULL)
        {
            return false;
        }

        $query = 
            'MATCH (t:Topic)-[:hasOccurrence]->(o:Occurrence { reifier: {reifier_id} })'
            . ' RETURN t.id AS topic_id, o.id AS occurrence_id';
        
        $bind = [ 'reifier_id' => $this->topic->getId() ];

        $logger->info($query, $bind);

        try
        {
            $qresult = $db_conn->run($query, $bind);
        }
        catch (Neo4jException $exception)
        {
            $logger->error($exception->getMessage());
            return false;
        }

        foreach ($qresult->records() as $record)
        {
            $topic = new Topic($this->topicmap);
            $ok = $topic->load($record->get('topic_id'));

            if ($ok < 0)
            {
                return false;
            }

            foreach ($topic->getOccurrences([ 'id' => $record->get('occurrence_id') ]) as $occurrence)
            {
                return
                    [
                        'topic' => $topic,
                        'occurrence' => $occurrence
                    ];
            }
        }
        
        return false;
    }
    
    
    protected function selectReifiedObject_Association()
    {
        $logger = $this->topicmap->getLogger();
        $db = $this->topicmap->getDb();

        $db_conn = $db->getConnection();

        if ($db_conn === NULL)
        {
            return false;
        }

        $query =
            'MATCH (a:Association { reifier: {reifier_id} })'
            . ' RETURN a.id AS association_id';

        $bind = [ 'reifier_id' => $this->topic->getId() ];

        $logger->info($query, $bind);

        try
        {
            $qresult = $db_conn->run($query, $bind);
        }
        catch (Neo4jException $exception)
        {
            $logger->error($exception->getMessage());
            return false;
        }

        foreach ($qresult->records() as $record)
        {
            $association = $this->topicmap->newAssociation();
            $association->load($record->get('association_id'));

            return
                [
                    'association' => $association
                ];
        }
        
        return false;
    }
    
    
    protected function selectReifiedObject_Role()
    {
        $logger = $this->topicmap->getLogger();
        $db = $this->topicmap->getDb();

        $db_conn = $db->getConnection();

        if ($db_conn === NULL)
        {
            return false;
        }

        $query =
            'MATCH (a:Association)-[r { reifier: {reifier_id} }]-()'
            . ' RETURN a.id AS association_id, r.id AS role_id';

        $bind = [ 'reifier_id' => $this->topic->getId() ];

        $logger->info($query, $bind);

        try
        {
            $qresult = $db_conn->run($query, $bind);
        }
        catch (Neo4jException $exception)
        {
            $logger->error($exception->getMessage());
            return false;
        }

        foreach ($qresult->records() as $record)
        {
            $association = new Association($this->topicmap);
            $ok = $association->load($record->get('association_id'));

            if ($ok < 0)
            {
                return false;
            }

            foreach ($association->getRoles([ 'id' => $record->get('role_id') ]) as $role)
            {
                return
                    [
                        'association' => $association,
                        'role' => $role
                    ];
            }
        }

        return false;
    }
    
    
    public function insertAll(array $data)
    {
        $logger = $this->topicmap->getLogger();
        $db = $this->topicmap->getDb();

        $db_conn = $db->getConnection();

        if ($db_conn === NULL)
        {
            return -1;
        }

        $now = date('c');
        
        $data[ 'created' ] = $data[ 'updated' ] = $now; 
        $data[ 'version' ] = 1;

        $property_data = [ ];

        foreach ([ 'created', 'id', 'reifies_id', 'reifies_what', 'subject_identifiers', 'subject_locators', 'updated', 'version' ] as $key)
        {
            $property_data[ $key ] = $data[ $key ];
        }
        
        $bind = [ ];
        $property_query = DbUtils::propertiesString($property_data, $bind);

        $classes = array_merge([ 'Topic' ], $data[ 'types' ]);

        $transaction = $db->beginTransaction();
        
        $query = sprintf
        (
            'CREATE (n%s { %s })',
            DbUtils::labelsString($classes),
            $property_query
        );

        $logger->info($query, $bind);
        
        $transaction->push($query, $bind);
        
        // Mark type topics

        $type_queries = DbUtils::tmConstructLabelQueries
        (
            $this->topicmap,
            $data[ 'types' ], 
            iTopicMap::SUBJECT_TOPIC_TYPE
        );
        
        foreach ($type_queries as $type_query)
        {
            $logger->info($type_query['query'], $type_query['bind']);
            $transaction->push($type_query['query'], $type_query['bind']);
        }

        // TODO: Error handling
        
        $ok = 1;

        if ($ok >= 0)
        {
            $name = new Name($this->topicmap);
            $ok = $name->getDbAdapter()->insertAll($data[ 'id' ], $data[ 'names' ], $transaction);
        }

        if ($ok >= 0)
        {
            $occurrence = new Occurrence($this->topicmap);
            $ok = $occurrence->getDbAdapter()->insertAll($data[ 'id' ], $data[ 'occurrences' ], $transaction);
        }

        try
        {
            $db->commit($transaction);
        }
        catch (Neo4jException $exception)
        {
            $logger->error($exception->getMessage());
            // TODO: Error handling
            $ok = -1;
        }

        // TODO: Error handling

        if ($ok >= 0)
        {
            $callback_result = [ ];
            
            $ok = $this->topicmap->trigger
            (
                iTopic::EVENT_SAVING, 
                [ 'topic' => $this, 'dml' => 'insert' ],
                $callback_result
            );
            
            if (isset($callback_result[ 'index_related' ]))
                $this->topic->getSearchAdapter()->addIndexRelated($callback_result[ 'index_related' ]);
        }
            
        return $ok;
    }


    public function updateAll(array $data)
    {
        $logger = $this->topicmap->getLogger();
        $db = $this->topicmap->getDb();

        $db_conn = $db->getConnection();

        if ($db_conn === NULL)
        {
            return -1;
        }
        
        $data[ 'updated' ] = date('c');        
        $data[ 'version' ]++;

        $previous_data = $this->topic->getPreviousData();
        $property_data = [ ];

        foreach ([ 'created', 'id', 'reifies_id', 'reifies_what', 'subject_identifiers', 'subject_locators', 'updated', 'version' ] as $key)
        {
            // Skip unmodified values
            
            // TODO previous_data must reference topic
            if (isset($previous_data[ $key ]) && (serialize($previous_data[ $key ]) === serialize($data[ $key ])))
            {
                continue;
            }
            
            $property_data[ $key ] = $data[ $key ];
        }
        
        $transaction = $db->beginTransaction();
        
        $bind = [ 'id' => $data[ 'id' ] ];
        $property_query = DbUtils::propertiesUpdateString('node', $property_data, $bind);

        $query = sprintf
        (
            'MATCH (node:Topic { id: {id} })%s',
            $property_query
        );

        if (isset($previous_data[ 'types' ]) && is_array($previous_data[ 'types' ]))
        {
            $previous_types = $previous_data[ 'types' ];
        }
        else
        {
            $previous_types = [ ];
        }

        $added_types = array_diff($data[ 'types' ], $previous_types);
        $removed_types = array_diff($previous_types, $data[ 'types' ]);

        if (count($removed_types) > 0)
        {
            $query .= sprintf
            (
                ' REMOVE node%s',
                DbUtils::labelsString($removed_types)
            );
        }

        if (count($added_types) > 0)
        {
            $query .= sprintf
            (
                ' SET node%s',
                DbUtils::labelsString($added_types)
            );
            
            // Mark type topics

            $type_queries = DbUtils::tmConstructLabelQueries
            (
                $this->topicmap,
                $added_types,
                iTopicMap::SUBJECT_TOPIC_TYPE
            );

            foreach ($type_queries as $type_query)
            {
                $logger->info($type_query['query'], $type_query['bind']);
                $transaction->push($type_query['query'], $type_query['bind']);
            }
        }

        $logger->info($query, $bind);
        $transaction->push($query, $bind);

        // TODO: Error handling
        $ok = 1;

        if ($ok >= 0)
        {
            $name = new Name($this->topicmap);
            
            $ok = $name->getDbAdapter()->updateAll
            (
                $data[ 'id' ], 
                $data[ 'names' ], 
                $previous_data[ 'names' ], 
                // Collect an array of queries instead of passing the transaction?
                $transaction
            );
        }

        if ($ok >= 0)
        {
            $occurrence = new Occurrence($this->topicmap);

            $occurrence->getDbAdapter()->updateAll
            (
                $data[ 'id' ],
                $data[ 'occurrences' ],
                $previous_data[ 'occurrences' ],
                // Collect an array of queries instead of passing the transaction?
                $transaction
            );
        }

        $ok = 1;

        try
        {
            $db->commit($transaction);
        }
        catch (Neo4jException $exception)
        {
            $logger->error($exception->getMessage());
            // TODO: Error handling
            $ok = -1;
        }
        
        if ($ok >= 0)
        {
            $callback_result = [ ];

            $ok = $this->topicmap->trigger
            (
                iTopic::EVENT_SAVING, 
                [ 'topic' => $this, 'dml' => 'update' ],
                $callback_result
            );

            if (isset($callback_result[ 'index_related' ]))
                $this->addIndexRelated($callback_result[ 'index_related' ]);
        }

        return $ok;
    }


    public function deleteById($id, $version)
    {
        // TODO: Implement $version

        $logger = $this->topicmap->getLogger();
        $db = $this->topicmap->getDb();

        $db_conn = $db->getConnection();

        if ($db_conn === NULL)
        {
            return -1;
        }

        $query = 
            'MATCH (node:Topic { id: {id} })'
            . ' OPTIONAL MATCH (node)-[rn:hasName]-(nn:Name)'
            . ' OPTIONAL MATCH (node)-[ro:hasOccurrence]-(no:Occurrence)'
            . ' DELETE nn, rn, no, ro, node';

        $bind = [ 'id' => $id ];

        $logger->info($query, $bind);
        
        $ok = 1;

        try
        {
            $db_conn->run($query, $bind);
        }
        catch (Neo4jException $exception)
        {
            $logger->error($exception->getMessage());
            // TODO: Error handling
            $ok = -1;
        }
        
        // TODO: error handling

        if ($ok >= 0)              
        {  
            $callback_result = [ ];

            $this->topicmap->trigger
            (
                iTopic::EVENT_DELETING, 
                [ 'topic_id' => $id ],
                $callback_result
            );

            if (isset($callback_result[ 'index_related' ]))
                $this->addIndexRelated($callback_result[ 'index_related' ]);
        }
            
        return 1;
    }
}
