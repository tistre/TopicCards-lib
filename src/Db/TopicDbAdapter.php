<?php

namespace TopicCards\Db;

use GraphAware\Neo4j\Client\Exception\Neo4jException;
use TopicCards\Interfaces\TopicInterface;
use TopicCards\Interfaces\TopicDbAdapterInterface;
use TopicCards\Interfaces\TopicMapInterface;
use TopicCards\Model\Association;
use TopicCards\Model\Name;
use TopicCards\Model\Occurrence;
use TopicCards\Model\Role;
use TopicCards\Model\Topic;


class TopicDbAdapter implements TopicDbAdapterInterface
{
    /** @var TopicInterface */
    protected $topic;

    /** @var TopicMapInterface */
    protected $topicMap;


    public function __construct(TopicInterface $topic)
    {
        $this->topic = $topic;
        $this->topicMap = $topic->getTopicMap();
    }


    public function selectAll(array $filters)
    {
        $logger = $this->topicMap->getLogger();
        $db = $this->topicMap->getDb();

        $dbConn = $db->getConnection();

        if ($dbConn === null) {
            return -1;
        }

        $query = 'MATCH (node:Topic { id: {id} }) RETURN node';
        $bind = ['id' => $filters['id']];

        $logger->info($query, $bind);

        try {
            $qResult = $dbConn->run($query, $bind);
        } catch (Neo4jException $exception) {
            $logger->error($exception->getMessage());

            // TODO: Error handling
            return -1;
        }

        // TODO add error handling

        $result = [];

        $name = new Name($this->topicMap);
        $occurrence = new Occurrence($this->topicMap);

        foreach ($qResult->records() as $record) {
            $node = $record->get('node');

            $row =
                [
                    'created' => $node->value('created'),
                    'id' => $node->value('id'),
                    'updated' => $node->value('updated'),
                    'version' => $node->value('version'),
                    'types' => array_values(array_diff($node->labels(), ['Topic'])),
                    'subject_identifiers' => [],
                    'subject_locators' => [],
                    'reifies_what' => ($node->hasValue('reifies_what') ? $node->value('reifies_what') : TopicInterface::REIFIES_NONE),
                    'reifies_id' => ($node->hasValue('reifies_id') ? $node->value('reifies_id') : '')
                ];

            // Subjects

            foreach (['subject_identifiers', 'subject_locators'] as $key) {
                if (! $node->hasValue($key)) {
                    continue;
                }

                $values = $node->value($key);

                if (! is_array($values)) {
                    $value = $values;
                    $values = [];

                    if (strlen($value) > 0) {
                        $values[] = $value;
                    }
                }

                $row[$key] = $values;
            }

            $row['names'] = $name->getDbAdapter()->selectAll(['topic' => $row['id']]);

            $row['occurrences'] = $occurrence->getDbAdapter()->selectAll(['topic' => $row['id']]);

            $result[] = $row;
        }

        return $result;
    }


    public function selectReifiedObject()
    {
        $reifiesWhat = $this->topic->getReifiesWhat();

        $map =
            [
                TopicInterface::REIFIES_NAME => 'Name',
                TopicInterface::REIFIES_OCCURRENCE => 'Occurrence',
                TopicInterface::REIFIES_ASSOCIATION => 'Association',
                TopicInterface::REIFIES_ROLE => 'Role'
            ];

        if (! isset($map[$reifiesWhat])) {
            return false;
        }

        $method = 'selectReifiedObject_' . $map[$reifiesWhat];

        return $this->$method();
    }


    protected function selectReifiedObject_Name()
    {
        $logger = $this->topicMap->getLogger();
        $db = $this->topicMap->getDb();

        $dbConn = $db->getConnection();

        if ($dbConn === null) {
            return false;
        }

        $query =
            'MATCH (t:Topic)-[:hasName]->(n:Name { reifier: {reifier_id} })'
            . ' RETURN t.id AS topic_id, n.id AS name_id';

        $bind = ['reifier_id' => $this->topic->getId()];

        $logger->info($query, $bind);

        try {
            $qResult = $dbConn->run($query, $bind);
        } catch (Neo4jException $exception) {
            $logger->error($exception->getMessage());

            return false;
        }

        foreach ($qResult->records() as $record) {
            $topic = new Topic($this->topicMap);
            $ok = $topic->load($record->get('topic_id'));

            if ($ok < 0) {
                return false;
            }

            foreach ($topic->getNames(['id' => $record->get('name_id')]) as $name) {
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
        $logger = $this->topicMap->getLogger();
        $db = $this->topicMap->getDb();

        $dbConn = $db->getConnection();

        if ($dbConn === null) {
            return false;
        }

        $query =
            'MATCH (t:Topic)-[:hasOccurrence]->(o:Occurrence { reifier: {reifier_id} })'
            . ' RETURN t.id AS topic_id, o.id AS occurrence_id';

        $bind = ['reifier_id' => $this->topic->getId()];

        $logger->info($query, $bind);

        try {
            $qResult = $dbConn->run($query, $bind);
        } catch (Neo4jException $exception) {
            $logger->error($exception->getMessage());

            return false;
        }

        foreach ($qResult->records() as $record) {
            $topic = new Topic($this->topicMap);
            $ok = $topic->load($record->get('topic_id'));

            if ($ok < 0) {
                return false;
            }

            foreach ($topic->getOccurrences(['id' => $record->get('occurrence_id')]) as $occurrence) {
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
        $logger = $this->topicMap->getLogger();
        $db = $this->topicMap->getDb();

        $dbConn = $db->getConnection();

        if ($dbConn === null) {
            return false;
        }

        $query =
            'MATCH (a:Association { reifier: {reifier_id} })'
            . ' RETURN a.id AS association_id';

        $bind = ['reifier_id' => $this->topic->getId()];

        $logger->info($query, $bind);

        try {
            $qResult = $dbConn->run($query, $bind);
        } catch (Neo4jException $exception) {
            $logger->error($exception->getMessage());

            return false;
        }

        foreach ($qResult->records() as $record) {
            $association = $this->topicMap->newAssociation();
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
        $logger = $this->topicMap->getLogger();
        $db = $this->topicMap->getDb();

        $dbConn = $db->getConnection();

        if ($dbConn === null) {
            return false;
        }

        $query =
            'MATCH (a:Association)-[r { reifier: {reifier_id} }]-()'
            . ' RETURN a.id AS association_id, r.id AS role_id';

        $bind = ['reifier_id' => $this->topic->getId()];

        $logger->info($query, $bind);

        try {
            $qResult = $dbConn->run($query, $bind);
        } catch (Neo4jException $exception) {
            $logger->error($exception->getMessage());

            return false;
        }

        foreach ($qResult->records() as $record) {
            $association = new Association($this->topicMap);
            $ok = $association->load($record->get('association_id'));

            if ($ok < 0) {
                return false;
            }

            foreach ($association->getRoles(['id' => $record->get('role_id')]) as $role) {
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
        $logger = $this->topicMap->getLogger();
        $db = $this->topicMap->getDb();

        $dbConn = $db->getConnection();

        if ($dbConn === null) {
            return -1;
        }

        $now = date('c');

        $data['created'] = $data['updated'] = $now;
        $data['version'] = 1;

        $propertyData = [];

        foreach ([
                     'created',
                     'id',
                     'reifies_id',
                     'reifies_what',
                     'subject_identifiers',
                     'subject_locators',
                     'updated',
                     'version'
                 ] as $key) {
            $propertyData[$key] = $data[$key];
        }

        $bind = [];
        $propertyQuery = DbUtils::propertiesString($propertyData, $bind);

        $classes = array_merge(['Topic'], $data['types']);

        $transaction = $db->beginTransaction();

        $query = sprintf
        (
            'CREATE (n%s { %s })',
            DbUtils::labelsString($classes),
            $propertyQuery
        );

        $logger->info($query, $bind);

        $transaction->push($query, $bind);

        // Mark type topics

        $typeQueries = DbUtils::tmConstructLabelQueries
        (
            $this->topicMap,
            $data['types'],
            TopicMapInterface::SUBJECT_TOPIC_TYPE
        );

        foreach ($typeQueries as $typeQuery) {
            $logger->info($typeQuery['query'], $typeQuery['bind']);
            $transaction->push($typeQuery['query'], $typeQuery['bind']);
        }

        // TODO: Error handling

        $ok = 1;

        if ($ok >= 0) {
            $name = new Name($this->topicMap);
            $ok = $name->getDbAdapter()->insertAll($data['id'], $data['names'], $transaction);
        }

        if ($ok >= 0) {
            $occurrence = new Occurrence($this->topicMap);
            $ok = $occurrence->getDbAdapter()->insertAll($data['id'], $data['occurrences'], $transaction);
        }

        try {
            $db->commit($transaction);
        } catch (Neo4jException $exception) {
            $logger->error($exception->getMessage());
            // TODO: Error handling
            $ok = -1;
        }

        // TODO: Error handling

        if ($ok >= 0) {
            $callbackResult = [];

            $ok = $this->topicMap->trigger
            (
                TopicInterface::EVENT_SAVING,
                ['topic' => $this->topic, 'dml' => 'insert'],
                $callbackResult
            );

            if (isset($callbackResult['index_related'])) {
                $this->topic->getSearchAdapter()->addIndexRelated($callbackResult['index_related']);
            }
        }

        return $ok;
    }


    public function updateAll(array $data)
    {
        $logger = $this->topicMap->getLogger();
        $db = $this->topicMap->getDb();

        $dbConn = $db->getConnection();

        if ($dbConn === null) {
            return -1;
        }

        $data['updated'] = date('c');
        $data['version']++;

        $previousData = $this->topic->getPreviousData();
        $propertyData = [];

        foreach ([
                     'created',
                     'id',
                     'reifies_id',
                     'reifies_what',
                     'subject_identifiers',
                     'subject_locators',
                     'updated',
                     'version'
                 ] as $key) {
            // Skip unmodified values

            // TODO previous_data must reference topic
            if (isset($previousData[$key]) && (serialize($previousData[$key]) === serialize($data[$key]))) {
                continue;
            }

            $propertyData[$key] = $data[$key];
        }

        $transaction = $db->beginTransaction();

        $bind = ['id' => $data['id']];
        $propertyQuery = DbUtils::propertiesUpdateString('node', $propertyData, $bind);

        $query = sprintf
        (
            'MATCH (node:Topic { id: {id} })%s',
            $propertyQuery
        );

        if (isset($previousData['types']) && is_array($previousData['types'])) {
            $previousTypes = $previousData['types'];
        } else {
            $previousTypes = [];
        }

        $addedTypes = array_diff($data['types'], $previousTypes);
        $removedTypes = array_diff($previousTypes, $data['types']);

        if (count($removedTypes) > 0) {
            $query .= sprintf
            (
                ' REMOVE node%s',
                DbUtils::labelsString($removedTypes)
            );
        }

        if (count($addedTypes) > 0) {
            $query .= sprintf
            (
                ' SET node%s',
                DbUtils::labelsString($addedTypes)
            );

            // Mark type topics

            $typeQueries = DbUtils::tmConstructLabelQueries
            (
                $this->topicMap,
                $addedTypes,
                TopicMapInterface::SUBJECT_TOPIC_TYPE
            );

            foreach ($typeQueries as $typeQuery) {
                $logger->info($typeQuery['query'], $typeQuery['bind']);
                $transaction->push($typeQuery['query'], $typeQuery['bind']);
            }
        }

        $logger->info($query, $bind);
        $transaction->push($query, $bind);

        // TODO: Error handling
        $ok = 1;

        if ($ok >= 0) {
            $name = new Name($this->topicMap);

            $ok = $name->getDbAdapter()->updateAll
            (
                $data['id'],
                $data['names'],
                $previousData['names'],
                // Collect an array of queries instead of passing the transaction?
                $transaction
            );
        }

        if ($ok >= 0) {
            $occurrence = new Occurrence($this->topicMap);

            $occurrence->getDbAdapter()->updateAll
            (
                $data['id'],
                $data['occurrences'],
                $previousData['occurrences'],
                // Collect an array of queries instead of passing the transaction?
                $transaction
            );
        }

        $ok = 1;

        try {
            $db->commit($transaction);
        } catch (Neo4jException $exception) {
            $logger->error($exception->getMessage());
            // TODO: Error handling
            $ok = -1;
        }

        if ($ok >= 0) {
            $callbackResult = [];

            $ok = $this->topicMap->trigger
            (
                TopicInterface::EVENT_SAVING,
                ['topic' => $this->topic, 'dml' => 'update'],
                $callbackResult
            );

            if (isset($callbackResult['index_related'])) {
                $this->addIndexRelated($callbackResult['index_related']);
            }
        }

        return $ok;
    }


    public function deleteById($id, $version)
    {
        // TODO: Implement $version

        $logger = $this->topicMap->getLogger();
        $db = $this->topicMap->getDb();

        $dbConn = $db->getConnection();

        if ($dbConn === null) {
            return -1;
        }

        $query =
            'MATCH (node:Topic { id: {id} })'
            . ' OPTIONAL MATCH (node)-[rn:hasName]-(nn:Name)'
            . ' OPTIONAL MATCH (node)-[ro:hasOccurrence]-(no:Occurrence)'
            . ' DELETE nn, rn, no, ro, node';

        $bind = ['id' => $id];

        $logger->info($query, $bind);

        $ok = 1;

        try {
            $dbConn->run($query, $bind);
        } catch (Neo4jException $exception) {
            $logger->error($exception->getMessage());
            // TODO: Error handling
            $ok = -1;
        }

        // TODO: error handling

        if ($ok >= 0) {
            $callbackResult = [];

            $this->topicMap->trigger
            (
                TopicInterface::EVENT_DELETING,
                ['topic_id' => $id],
                $callbackResult
            );

            if (isset($callbackResult['index_related'])) {
                $this->addIndexRelated($callbackResult['index_related']);
            }
        }

        return 1;
    }
}
