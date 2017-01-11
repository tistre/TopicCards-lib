<?php

namespace TopicCards\Db;

use GraphAware\Neo4j\Client\Exception\Neo4jException;
use \TopicCards\Interfaces\AssociationInterface;
use TopicCards\Interfaces\TopicInterface;
use TopicCards\Interfaces\TopicMapInterface;
use TopicCards\Interfaces\PersistentDbAdapterInterface;
use TopicCards\Model\Role;


class AssociationDbAdapter implements PersistentDbAdapterInterface
{
    /** @var AssociationInterface */
    protected $association;

    /** @var TopicMapInterface */
    protected $topicmap;


    public function __construct(AssociationInterface $association)
    {
        $this->association = $association;
        $this->topicmap = $association->getTopicMap();
    }


    public function selectAll(array $filters)
    {
        $logger = $this->topicmap->getLogger();
        $db = $this->topicmap->getDb();

        $db_conn = $db->getConnection();

        if ($db_conn === null) {
            return -1;
        }

        $property_data = [];

        foreach (['id', 'reifier', 'scope'] as $key) {
            if (isset($filters[$key])) {
                $property_data[$key] = $filters[$key];
            }
        }

        $bind = [];
        $property_query = DbUtils::propertiesString($property_data, $bind);

        $query = sprintf
        (
            'MATCH (node:Association { %s }) RETURN node',
            $property_query
        );

        $logger->info($query, $bind);

        try {
            $qresult = $db_conn->run($query, $bind);
        } catch (Neo4jException $exception) {
            $logger->error($exception->getMessage());

            // TODO: Error handling
            return -1;
        }

        // TODO add error handling

        $result = [];

        $role = new Role($this->topicmap);

        foreach ($qresult->records() as $record) {
            $node = $record->get('node');

            $row =
                [
                    'created' => $node->value('created'),
                    'id' => $node->value('id'),
                    'updated' => $node->value('updated'),
                    'version' => $node->value('version'),
                    'scope' => [],
                    'reifier' => ($node->hasValue('reifier') ? $node->value('reifier') : false)
                ];

            // Type

            $types = array_values(array_diff($node->labels(), ['Association']));
            $row['type'] = $types[0];

            // Scope

            if ($node->hasValue('scope')) {
                $row['scope'] = $node->value('scope');

                if (! is_array($row['scope'])) {
                    $value = $row['scope'];
                    $row['scope'] = [];

                    if (strlen($value) > 0) {
                        $row['scope'][] = $value;
                    }
                }
            }

            $row['roles'] = $role->getDbAdapter()->selectAll(['association' => $row['id']]);

            $result[] = $row;
        }

        return $result;
    }


    public function insertAll(array $data)
    {
        $logger = $this->topicmap->getLogger();
        $db = $this->topicmap->getDb();

        $db_conn = $db->getConnection();

        if ($db_conn === null) {
            return -1;
        }

        $now = date('c');

        $data['created'] = $data['updated'] = $now;
        $data['version'] = 1;

        if (empty($data['scope'])) {
            $data['scope'] = [];
        } elseif (! is_array($data['scope'])) {
            $data['scope'] = [$data['scope']];
        }

        $property_data = [];

        foreach (['created', 'id', 'updated', 'version', 'scope', 'reifier'] as $key) {
            $property_data[$key] = $data[$key];
        }

        $bind = [];
        $property_query = DbUtils::propertiesString($property_data, $bind);

        $classes = ['Association', $data['type']];

        $transaction = $db->beginTransaction();

        $query = sprintf
        (
            'CREATE (n%s { %s })',
            DbUtils::labelsString($classes),
            $property_query
        );

        $logger->info($query, $bind);

        $transaction->push($query, $bind);

        // TODO: Add a relation to the reifier topic!

        // Mark type topics

        $type_queries = DbUtils::tmConstructLabelQueries
        (
            $this->topicmap,
            [$data['type']],
            TopicMapInterface::SUBJECT_ASSOCIATION_TYPE
        );

        $type_queries = array_merge($type_queries, DbUtils::tmConstructLabelQueries
        (
            $this->topicmap,
            $data['scope'],
            TopicMapInterface::SUBJECT_SCOPE
        ));

        foreach ($type_queries as $type_query) {
            $logger->info($type_query['query'], $type_query['bind']);
            $transaction->push($type_query['query'], $type_query['bind']);
        }

        // Link reifier

        if (strlen($data['reifier']) > 0) {
            $reifier_queries = DbUtils::tmConstructLinkReifierQueries
            (
                TopicInterface::REIFIES_ASSOCIATION,
                $data['id'],
                $data['reifier']
            );

            foreach ($reifier_queries as $query) {
                $logger->info($query['query'], $query['bind']);
                $transaction->push($query['query'], $query['bind']);
            }
        }

        // TODO: Error handling

        $role = new Role($this->topicmap);
        $ok = $role->getDbAdapter()->insertAll($data['id'], $data['roles'], $transaction);

        try {
            $db->commit($transaction);
        } catch (Neo4jException $exception) {
            $logger->error($exception->getMessage());
            $db->rollBack($transaction);

            // TODO: Error handling
            $ok = -1;
        }

        if ($ok >= 0) {
            $callback_result = [];

            $ok = $this->topicmap->trigger
            (
                AssociationInterface::EVENT_SAVING,
                ['association' => $this->association, 'dml' => 'insert'],
                $callback_result
            );

            if (isset($callback_result['index_related'])) {
                $this->association->getSearchAdapter()->addIndexRelated($callback_result['index_related']);
            }
        }

        return $ok;
    }


    public function updateAll(array $data)
    {
        $logger = $this->topicmap->getLogger();
        $db = $this->topicmap->getDb();

        $db_conn = $db->getConnection();

        if ($db_conn === null) {
            return -1;
        }

        $data['updated'] = date('c');
        $data['version']++;

        if (empty($data['scope'])) {
            $data['scope'] = [];
        } elseif (! is_array($data['scope'])) {
            $data['scope'] = [$data['scope']];
        }

        if (! isset($data['reifier'])) {
            $data['reifier'] = false;
        }

        $transaction = $db->beginTransaction();

        $property_data = [];
        $previous_data = $this->association->getPreviousData();

        foreach (['created', 'id', 'updated', 'version', 'scope', 'reifier'] as $key) {
            // Skip unmodified values

            if (isset($previous_data[$key]) && (serialize($previous_data[$key]) === serialize($data[$key]))) {
                continue;
            }

            $property_data[$key] = $data[$key];

            if ($key === 'scope') {
                // Mark type topics

                $type_queries = DbUtils::tmConstructLabelQueries
                (
                    $this->topicmap,
                    $data[$key],
                    TopicMapInterface::SUBJECT_SCOPE
                );

                foreach ($type_queries as $type_query) {
                    $logger->info($type_query['query'], $type_query['bind']);
                    $transaction->push($type_query['query'], $type_query['bind']);
                }
            }
        }

        $bind = ['id' => $data['id']];
        $property_query = DbUtils::propertiesUpdateString('node', $property_data, $bind);

        $query = sprintf
        (
            'MATCH (node:Association { id: {id} })%s',
            $property_query
        );

        if ($previous_data['type'] !== $data['type']) {
            $query .= sprintf
            (
                ' REMOVE node%s',
                DbUtils::labelsString([$previous_data['type']])
            );

            $query .= sprintf
            (
                ' SET node%s',
                DbUtils::labelsString([$data['type']])
            );

            // Mark type topics

            $type_queries = DbUtils::tmConstructLabelQueries
            (
                $this->topicmap,
                [$data['type']],
                TopicMapInterface::SUBJECT_ASSOCIATION_ROLE_TYPE
            );

            foreach ($type_queries as $type_query) {
                $logger->info($type_query['query'], $type_query['bind']);
                $transaction->push($type_query['query'], $type_query['bind']);
            }
        }

        $logger->info($query, $bind);

        $transaction->push($query, $bind);

        // Link reifier

        if ($data['reifier'] !== $previous_data['reifier']) {
            if (strlen($data['reifier']) > 0) {
                $reifier_queries = DbUtils::tmConstructLinkReifierQueries
                (
                    TopicInterface::REIFIES_ASSOCIATION,
                    $data['id'],
                    $data['reifier']
                );
            } else {
                $reifier_queries = DbUtils::tmConstructUnlinkReifierQueries
                (
                    TopicInterface::REIFIES_ASSOCIATION,
                    $data['id'],
                    $previous_data['reifier']
                );
            }

            foreach ($reifier_queries as $query) {
                $logger->info($query['query'], $query['bind']);
                $transaction->push($query['query'], $query['bind']);
            }
        }

        // TODO: Error handling
        $ok = 1;

        if ($ok >= 0) {
            $role = new Role($this->topicmap);

            $role->getDbAdapter()->updateAll
            (
                $data['id'],
                $data['roles'],
                $previous_data['roles'],
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
            $callback_result = [];

            $ok = $this->topicmap->trigger
            (
                AssociationInterface::EVENT_SAVING,
                ['association' => $this->association, 'dml' => 'update'],
                $callback_result
            );

            if (isset($callback_result['index_related'])) {
                $this->association->getSearchAdapter()->addIndexRelated($callback_result['index_related']);
            }
        }

        return $ok;
    }


    public function deleteById($id, $version)
    {
        // TODO: Implement $version

        $logger = $this->topicmap->getLogger();
        $db = $this->topicmap->getDb();

        $db_conn = $db->getConnection();

        if ($db_conn === null) {
            return -1;
        }

        $query =
            'MATCH (node:Association { id: {id} })'
            . ' OPTIONAL MATCH (node)-[r]-()'
            . ' DELETE r, node';

        $bind = ['id' => $id];

        $logger->info($query, $bind);

        $ok = 1;

        try {
            $db_conn->run($query, $bind);
        } catch (Neo4jException $exception) {
            $logger->error($exception->getMessage());
            // TODO: Error handling
            $ok = -1;
        }

        // TODO: error handling

        if ($ok >= 0) {
            $callback_result = [];

            $this->topicmap->trigger
            (
                AssociationInterface::EVENT_DELETING,
                ['association_id' => $id],
                $callback_result
            );

            if (isset($callback_result['index_related'])) {
                $this->association->getSearchAdapter()->addIndexRelated($callback_result['index_related']);
            }
        }

        return 1;
    }
}
