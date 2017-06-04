<?php

namespace TopicCards\Db;

use GraphAware\Neo4j\Client\Exception\Neo4jException;
use GraphAware\Neo4j\Client\Transaction\Transaction;
use TopicCards\Exception\TopicCardsLogicException;
use TopicCards\Exception\TopicCardsRuntimeException;
use TopicCards\Interfaces\RoleInterface;
use TopicCards\Interfaces\RoleDbAdapterInterface;
use TopicCards\Interfaces\TopicInterface;
use TopicCards\Interfaces\TopicMapInterface;


class RoleDbAdapter implements RoleDbAdapterInterface
{
    /** @var RoleInterface */
    protected $role;

    /** @var TopicMapInterface */
    protected $topicMap;


    /**
     * RoleDbAdapter constructor.
     *
     * @param RoleInterface $role
     */
    public function __construct(RoleInterface $role)
    {
        $this->role = $role;
        $this->topicMap = $role->getTopicMap();
    }


    /**
     * @param array $filters
     * @return array|int
     */
    public function selectAll(array $filters)
    {
        $logger = $this->topicMap->getLogger();
        $db = $this->topicMap->getDb();

        $dbConn = $db->getConnection();

        if ($dbConn === null) {
            throw new TopicCardsRuntimeException(sprintf
            (
                '%s: Failed to get db connection.',
                __METHOD__
            ));
        }

        if (! empty($filters['reifier'])) {
            throw new TopicCardsLogicException
            (
                sprintf('%s: "reifier" filter not implemented yet.', __METHOD__)
            );
        }

        if (! isset($filters['association'])) {
            throw new TopicCardsLogicException
            (
                sprintf('%s: "association" filter not implemented yet.', __METHOD__)
            );
        }

        $query = 'MATCH (assoc:Association { id: {id} })-[rel]-(node:Topic) RETURN assoc, rel, node';
        $bind = ['id' => $filters['association']];

        $logger->info($query, $bind);

        try {
            $qResult = $dbConn->run($query, $bind);
        } catch (Neo4jException $exception) {
            throw new TopicCardsRuntimeException
            (
                sprintf
                (
                    '%s: Neo4j run failed.',
                    __METHOD__
                ),
                0,
                $exception
            );
        }

        $result = [];

        foreach ($qResult->records() as $record) {
            $rel = $record->get('rel');
            // TODO: Only fetch the topic ID, not the whole topic
            $node = $record->get('node');
            // TODO: Only fetch the association ID, not the whole association
            $assoc = $record->get('assoc');

            $row =
                [
                    'id' => ($rel->hasValue('id') ? $rel->value('id') : false),
                    'reifier' => ($rel->hasValue('reifier') ? $rel->value('reifier') : false),
                    'association' => ($assoc->hasValue('id') ? $assoc->value('id') : false),
                    'player' => ($node->hasValue('id') ? $node->value('id') : false)
                ];

            // Type

            $row['type'] = $rel->type();

            $result[] = $row;
        }

        return $result;
    }


    /**
     * @param string $associationId
     * @param array $data
     * @param Transaction $transaction
     * @return int
     */
    public function insertAll($associationId, array $data, Transaction $transaction)
    {
        foreach ($data as $roleData) {
            $this->insertRole($associationId, $roleData, $transaction);
        }

        // TODO: error handling

        return 1;
    }


    /**
     * @param string $associationId
     * @param array $data
     * @param array $previousData
     * @param Transaction $transaction
     * @return int
     */
    public function updateAll($associationId, array $data, array $previousData, Transaction $transaction)
    {
        $ok = 1;

        foreach ($data as $roleData) {
            // No ID? Must be a new role

            if (empty($roleData['id'])) {
                $ok = $this->insertRole($associationId, $roleData, $transaction);

                if ($ok < 0) {
                    return $ok;
                }

                continue;
            }

            // If the ID is not in $previous_data, it's a new role

            $found = false;
            $previousRoleData = [];

            foreach ($previousData as $previousRoleData) {
                if ($previousRoleData['id'] === $roleData['id']) {
                    $found = true;
                    break;
                }
            }

            if (! $found) {
                $ok = $this->insertRole($associationId, $roleData, $transaction);

                if ($ok < 0) {
                    return $ok;
                }

                continue;
            }

            // It's an updated role...

            $ok = $this->updateRole($associationId, $roleData, $previousRoleData, $transaction);

            if ($ok < 0) {
                return $ok;
            }

            // TODO: handle role deletion, or empty value
        }

        // TODO: error handling
        return $ok;
    }


    /**
     * @param string $associationId
     * @param array $data
     * @param Transaction $transaction
     * @return void
     * @throws TopicCardsLogicException
     */
    protected function insertRole($associationId, array $data, Transaction $transaction)
    {
        $logger = $this->topicMap->getLogger();

        if ((! isset($data['player'])) || (strlen($data['player']) === 0)) {
            return;
        }

        if (empty($data['type'])) {
            throw new TopicCardsLogicException
            (
                '%s: Cannot add role to association <%s>, required "type" data is empty.',
                __METHOD__, $associationId
            );
        }

        if (empty($data['id'])) {
            $data['id'] = $this->topicMap->createId();
        }

        if (! isset($data['reifier'])) {
            $data['reifier'] = false;
        }

        $propertyData =
            [
                'id' => $data['id'],
                'reifier' => $data['reifier']
            ];

        $bind =
            [
                'association_id' => $associationId,
                'topic_id' => $data['player']
            ];

        $propertyQuery = DbUtils::propertiesString($propertyData, $bind);

        $classes = [$data['type']];

        $query = sprintf
        (
            'MATCH (a:Association), (t:Topic)'
            . ' WHERE a.id = {association_id} AND t.id = {topic_id}'
            . ' CREATE (a)-[r%s { %s }]->(t)',
            DbUtils::labelsString($classes),
            $propertyQuery
        );

        $logger->info($query, $bind);

        $transaction->push($query, $bind);

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

        // Link reifier

        if (strlen($data['reifier']) > 0) {
            $reifierQueries = DbUtils::tmConstructLinkReifierQueries
            (
                TopicInterface::REIFIES_ROLE,
                $data['id'],
                $data['reifier']
            );

            foreach ($reifierQueries as $query) {
                $logger->info($query['query'], $query['bind']);
                $transaction->push($query['query'], $query['bind']);
            }
        }
    }


    /**
     * @param string $associationId
     * @param array $data
     * @param array $previousData
     * @param Transaction $transaction
     */
    protected function updateRole($associationId, array $data, array $previousData, Transaction $transaction)
    {
        $logger = $this->topicMap->getLogger();

        $doDelete = $doInsert = false;

        if (! isset($data['reifier'])) {
            $data['reifier'] = false;
        }

        if ((! isset($data['player'])) || (strlen($data['player']) === 0)) {
            $doDelete = true;
        } elseif (($previousData['player'] !== $data['player']) || ($previousData['type'] !== $data['type']) || ($previousData['reifier'] !== $data['reifier'])) {
            $doDelete = $doInsert = true;
        }

        if ($doDelete) {
            // Unlink reifier

            if (strlen($data['reifier']) > 0) {
                $reifierQueries = DbUtils::tmConstructUnlinkReifierQueries
                (
                    TopicInterface::REIFIES_ROLE,
                    $data['id'],
                    $data['reifier']
                );

                foreach ($reifierQueries as $query) {
                    $logger->info($query['query'], $query['bind']);
                    $transaction->push($query['query'], $query['bind']);
                }
            }

            $bind =
                [
                    'id' => $data['id'],
                    'association_id' => $associationId,
                    'player_id' => $previousData['player']
                ];

            $query = 'MATCH (a:Association { id: {association_id} })-[r { id: {id} }]-(t:Topic { id: {player_id} }) DELETE r';

            $logger->info($query, $bind);

            $transaction->push($query, $bind);
        }

        if ($doInsert) {
            $this->insertRole($associationId, $data, $transaction);
        }
    }
}
