<?php

namespace TopicCards\Db;

use GraphAware\Neo4j\Client\Exception\Neo4jException;
use GraphAware\Neo4j\Client\Transaction\Transaction;
use TopicCards\Exception\TopicCardsLogicException;
use TopicCards\Exception\TopicCardsRuntimeException;
use TopicCards\Interfaces\NameInterface;
use TopicCards\Interfaces\NameDbAdapterInterface;
use TopicCards\Interfaces\TopicInterface;
use TopicCards\Interfaces\TopicMapInterface;


class NameDbAdapter implements NameDbAdapterInterface
{
    /** @var NameInterface */
    protected $name;

    /** @var TopicMapInterface */
    protected $topicMap;


    /**
     * NameDbAdapter constructor.
     *
     * @param NameInterface $name
     */
    public function __construct(NameInterface $name)
    {
        $this->name = $name;
        $this->topicMap = $name->getTopicMap();
    }


    /**
     * @param array $filters
     * @return array|int Negative number on error
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

        if (! isset($filters['topic'])) {
            throw new TopicCardsLogicException
            (
                sprintf('%s: Required "topic" filter is empty.', __METHOD__)
            );
        }

        $query = 'MATCH (t:Topic { id: {id} })-[:hasName]->(node:Name) RETURN node';
        $bind = ['id' => $filters['topic']];

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
            $node = $record->get('node');

            $row =
                [
                    'id' => ($node->hasValue('id') ? $node->value('id') : false),
                    'value' => ($node->hasValue('value') ? $node->value('value') : false),
                    'datatype' => ($node->hasValue('datatype') ? $node->value('datatype') : false),
                    'language' => ($node->hasValue('language') ? $node->value('language') : false),
                    'scope' => [],
                    'reifier' => ($node->hasValue('reifier') ? $node->value('reifier') : false),
                ];

            // Type

            $types = array_values(array_diff($node->labels(), ['Name']));
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

            $result[] = $row;
        }

        return $result;
    }


    /**
     * @param string $topic_id
     * @param array $data
     * @param Transaction $transaction
     * @return void
     */
    public function insertAll($topicId, array $data, Transaction $transaction)
    {
        foreach ($data as $nameData) {
            $this->insertName($topicId, $nameData, $transaction);
        }
    }


    /**
     * @param string $topic_id
     * @param array $data
     * @param array $previous_data
     * @param Transaction $transaction
     * @return void
     */
    public function updateAll($topicId, array $data, array $previousData, Transaction $transaction)
    {
        $previousNameData = [];

        foreach ($data as $nameData) {
            // No ID? Must be a new name

            if (empty($nameData['id'])) {
                $this->insertName($topicId, $nameData, $transaction);
                continue;
            }

            // If the ID is not in $previous_data, it's a new name

            $found = false;

            foreach ($previousData as $previousNameData) {
                if ($previousNameData['id'] === $nameData['id']) {
                    $found = true;
                    break;
                }
            }

            if (! $found) {
                $this->insertName($topicId, $nameData, $transaction);
                continue;
            }

            // It's an updated name...

            $this->updateName($topicId, $nameData, $previousNameData, $transaction);

            // TODO: handle name deletion, or empty value
        }
    }


    /**
     * @param $topicId
     * @param array $data
     * @param Transaction $transaction
     * @return void
     * @throws TopicCardsLogicException
     */
    protected function insertName($topicId, array $data, Transaction $transaction)
    {
        $logger = $this->topicMap->getLogger();

        if ((! isset($data['value'])) || (strlen($data['value']) === 0)) {
            return;
        }

        if (empty($data['type'])) {
            throw new TopicCardsLogicException
            (
                '%s: Cannot add name to topic <%s>, required "type" data is empty.',
                __METHOD__, $topicId
            );
        }

        if (empty($data['id'])) {
            $data['id'] = $this->topicMap->createId();
        }

        if (empty($data['language'])) {
            $data['language'] = false;
        }

        if (empty($data['scope'])) {
            $data['scope'] = [];
        } elseif (! is_array($data['scope'])) {
            $data['scope'] = [$data['scope']];
        }

        if (empty($data['reifier'])) {
            $data['reifier'] = false;
        }

        $propertyData =
            [
                'id' => $data['id'],
                'value' => $data['value'],
                'datatype' => $data['datatype'],
                'language' => $data['language'],
                'scope' => $data['scope'],
                'reifier' => $data['reifier']
            ];

        $bind = ['topic_id' => $topicId];

        $propertyQuery = DbUtils::propertiesString($propertyData, $bind);

        $classes = ['Name', $data['type']];

        $query = sprintf
        (
            'MATCH (a:Topic { id: {topic_id} })'
            . ' CREATE (a)-[:hasName]->(b%s { %s })',
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
            TopicMapInterface::SUBJECT_TOPIC_NAME_TYPE
        );

        $typeQueries = array_merge($typeQueries, DbUtils::tmConstructLabelQueries
        (
            $this->topicMap,
            $data['scope'],
            TopicMapInterface::SUBJECT_SCOPE
        ));

        $typeQueries = array_merge($typeQueries, DbUtils::tmConstructLabelQueries
        (
            $this->topicMap,
            [$data['datatype']],
            TopicMapInterface::SUBJECT_DATATYPE
        ));

        foreach ($typeQueries as $typeQuery) {
            $logger->info($typeQuery['query'], $typeQuery['bind']);
            $transaction->push($typeQuery['query'], $typeQuery['bind']);
        }

        // Link reifier

        if (strlen($data['reifier']) > 0) {
            $reifierQueries = DbUtils::tmConstructLinkReifierQueries
            (
                TopicInterface::REIFIES_NAME,
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
     * @param $topicId
     * @param array $data
     * @param array $previousData
     * @param Transaction $transaction
     * @return void
     */
    protected function updateName($topicId, array $data, array $previousData, Transaction $transaction)
    {
        $logger = $this->topicMap->getLogger();

        if ((! isset($data['value'])) || (strlen($data['value']) === 0)) {
            $bind = ['id' => $data['id']];
            $query = 'MATCH (node:Name { id: {id} }) OPTIONAL MATCH (node)-[r:hasName]-() DELETE r, node';

            $logger->info($query, $bind);

            $transaction->push($query, $bind);

            return;
        }

        if (empty($data['scope'])) {
            $data['scope'] = [];
        } elseif (! is_array($data['scope'])) {
            $data['scope'] = [$data['scope']];
        }

        if (! isset($data['reifier'])) {
            $data['reifier'] = false;
        }

        $propertyData = [];

        foreach (['value', 'datatype', 'language', 'scope', 'reifier'] as $key) {
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

        // Skip update if no property changes and no type change!
        $dirty = (strlen($propertyQuery) > 0);

        $query = sprintf
        (
            'MATCH (node:Name { id: {id} })%s',
            $propertyQuery
        );

        if ($data['type'] !== $previousData['type']) {
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
                TopicMapInterface::SUBJECT_TOPIC_NAME_TYPE
            );

            foreach ($typeQueries as $typeQuery) {
                $logger->info($typeQuery['query'], $typeQuery['bind']);
                $transaction->push($typeQuery['query'], $typeQuery['bind']);
            }

            $dirty = true;
        }

        if ($dirty) {
            $logger->info($query, $bind);
            $transaction->push($query, $bind);
        }

        // Link reifier

        if ($data['reifier'] !== $previousData['reifier']) {
            if (strlen($data['reifier']) > 0) {
                $reifierQueries = DbUtils::tmConstructLinkReifierQueries
                (
                    TopicInterface::REIFIES_NAME,
                    $data['id'],
                    $data['reifier']
                );
            } else {
                $reifierQueries = DbUtils::tmConstructUnlinkReifierQueries
                (
                    TopicInterface::REIFIES_NAME,
                    $data['id'],
                    $previousData['reifier']
                );
            }

            foreach ($reifierQueries as $query) {
                $logger->info($query['query'], $query['bind']);
                $transaction->push($query['query'], $query['bind']);
            }
        }
    }
}
