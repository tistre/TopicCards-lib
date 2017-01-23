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
    protected $topicMap;


    public function __construct(AssociationInterface $association)
    {
        $this->association = $association;
        $this->topicMap = $association->getTopicMap();
    }


    public function selectAll(array $filters)
    {
        $logger = $this->topicMap->getLogger();
        $db = $this->topicMap->getDb();

        $dbConn = $db->getConnection();

        if ($dbConn === null) {
            return -1;
        }

        $propertyData = [];

        foreach (['id', 'reifier', 'scope'] as $key) {
            if (isset($filters[$key])) {
                $propertyData[$key] = $filters[$key];
            }
        }

        $bind = [];
        $propertyQuery = DbUtils::propertiesString($propertyData, $bind);

        $query = sprintf
        (
            'MATCH (node:Association { %s }) RETURN node',
            $propertyQuery
        );

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

        $role = new Role($this->topicMap);

        foreach ($qResult->records() as $record) {
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
        $logger = $this->topicMap->getLogger();
        $db = $this->topicMap->getDb();

        $dbConn = $db->getConnection();

        if ($dbConn === null) {
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

        $propertyData = [];

        foreach (['created', 'id', 'updated', 'version', 'scope', 'reifier'] as $key) {
            $propertyData[$key] = $data[$key];
        }

        $bind = [];
        $propertyQuery = DbUtils::propertiesString($propertyData, $bind);

        $classes = ['Association', $data['type']];

        $transaction = $db->beginTransaction();

        $query = sprintf
        (
            'CREATE (n%s { %s })',
            DbUtils::labelsString($classes),
            $propertyQuery
        );

        $logger->info($query, $bind);

        $transaction->push($query, $bind);

        // TODO: Add a relation to the reifier topic!

        // Mark type topics

        $typeQueries = DbUtils::tmConstructLabelQueries
        (
            $this->topicMap,
            [$data['type']],
            TopicMapInterface::SUBJECT_ASSOCIATION_TYPE
        );

        $typeQueries = array_merge($typeQueries, DbUtils::tmConstructLabelQueries
        (
            $this->topicMap,
            $data['scope'],
            TopicMapInterface::SUBJECT_SCOPE
        ));

        foreach ($typeQueries as $typeQuery) {
            $logger->info($typeQuery['query'], $typeQuery['bind']);
            $transaction->push($typeQuery['query'], $typeQuery['bind']);
        }

        // Link reifier

        if (strlen($data['reifier']) > 0) {
            $reifierQueries = DbUtils::tmConstructLinkReifierQueries
            (
                TopicInterface::REIFIES_ASSOCIATION,
                $data['id'],
                $data['reifier']
            );

            foreach ($reifierQueries as $query) {
                $logger->info($query['query'], $query['bind']);
                $transaction->push($query['query'], $query['bind']);
            }
        }

        // TODO: Error handling

        $role = new Role($this->topicMap);
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
            $callbackResult = [];

            $ok = $this->topicMap->trigger
            (
                AssociationInterface::EVENT_SAVING,
                ['association' => $this->association, 'dml' => 'insert'],
                $callbackResult
            );

            if (isset($callbackResult['index_related'])) {
                $this->association->getSearchAdapter()->addIndexRelated($callbackResult['index_related']);
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

        if (empty($data['scope'])) {
            $data['scope'] = [];
        } elseif (! is_array($data['scope'])) {
            $data['scope'] = [$data['scope']];
        }

        if (! isset($data['reifier'])) {
            $data['reifier'] = false;
        }

        $transaction = $db->beginTransaction();

        $propertyData = [];
        $previousData = $this->association->getPreviousData();

        foreach (['created', 'id', 'updated', 'version', 'scope', 'reifier'] as $key) {
            // Skip unmodified values

            if (isset($previousData[$key]) && (serialize($previousData[$key]) === serialize($data[$key]))) {
                continue;
            }

            $propertyData[$key] = $data[$key];

            if ($key === 'scope') {
                // Mark type topics

                $typeQueries = DbUtils::tmConstructLabelQueries
                (
                    $this->topicMap,
                    $data[$key],
                    TopicMapInterface::SUBJECT_SCOPE
                );

                foreach ($typeQueries as $typeQuery) {
                    $logger->info($typeQuery['query'], $typeQuery['bind']);
                    $transaction->push($typeQuery['query'], $typeQuery['bind']);
                }
            }
        }

        $bind = ['id' => $data['id']];
        $propertyQuery = DbUtils::propertiesUpdateString('node', $propertyData, $bind);

        $query = sprintf
        (
            'MATCH (node:Association { id: {id} })%s',
            $propertyQuery
        );

        if ($previousData['type'] !== $data['type']) {
            $query .= sprintf
            (
                ' REMOVE node%s',
                DbUtils::labelsString([$previousData['type']])
            );

            $query .= sprintf
            (
                ' SET node%s',
                DbUtils::labelsString([$data['type']])
            );

            // Mark type topics

            $typeQueries = DbUtils::tmConstructLabelQueries
            (
                $this->topicMap,
                [$data['type']],
                TopicMapInterface::SUBJECT_ASSOCIATION_ROLE_TYPE
            );

            foreach ($typeQueries as $typeQuery) {
                $logger->info($typeQuery['query'], $typeQuery['bind']);
                $transaction->push($typeQuery['query'], $typeQuery['bind']);
            }
        }

        $logger->info($query, $bind);

        $transaction->push($query, $bind);

        // Link reifier

        if ($data['reifier'] !== $previousData['reifier']) {
            if (strlen($data['reifier']) > 0) {
                $reifierQueries = DbUtils::tmConstructLinkReifierQueries
                (
                    TopicInterface::REIFIES_ASSOCIATION,
                    $data['id'],
                    $data['reifier']
                );
            } else {
                $reifierQueries = DbUtils::tmConstructUnlinkReifierQueries
                (
                    TopicInterface::REIFIES_ASSOCIATION,
                    $data['id'],
                    $previousData['reifier']
                );
            }

            foreach ($reifierQueries as $query) {
                $logger->info($query['query'], $query['bind']);
                $transaction->push($query['query'], $query['bind']);
            }
        }

        // TODO: Error handling
        $ok = 1;

        if ($ok >= 0) {
            $role = new Role($this->topicMap);

            $role->getDbAdapter()->updateAll
            (
                $data['id'],
                $data['roles'],
                $previousData['roles'],
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
                AssociationInterface::EVENT_SAVING,
                ['association' => $this->association, 'dml' => 'update'],
                $callbackResult
            );

            if (isset($callbackResult['index_related'])) {
                $this->association->getSearchAdapter()->addIndexRelated($callbackResult['index_related']);
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
            'MATCH (node:Association { id: {id} })'
            . ' OPTIONAL MATCH (node)-[r]-()'
            . ' DELETE r, node';

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
                AssociationInterface::EVENT_DELETING,
                ['association_id' => $id],
                $callbackResult
            );

            if (isset($callbackResult['index_related'])) {
                $this->association->getSearchAdapter()->addIndexRelated($callbackResult['index_related']);
            }
        }

        return 1;
    }
}
