<?php

namespace TopicCards\Db;

use GraphAware\Neo4j\Client\Exception\Neo4jException;
use GraphAware\Neo4j\Client\Transaction\Transaction;
use TopicCards\Interfaces\OccurrenceInterface;
use TopicCards\Interfaces\OccurrenceDbAdapterInterface;
use TopicCards\Interfaces\TopicInterface;
use TopicCards\Interfaces\TopicMapInterface;


class OccurrenceDbAdapter implements OccurrenceDbAdapterInterface
{
    /** @var OccurrenceInterface */
    protected $occurrence;

    /** @var TopicMapInterface */
    protected $topicMap;


    public function __construct(OccurrenceInterface $occurrence)
    {
        $this->occurrence = $occurrence;
        $this->topicMap = $occurrence->getTopicMap();
    }


    public function selectAll(array $filters)
    {
        $logger = $this->topicMap->getLogger();
        $db = $this->topicMap->getDb();

        $dbConn = $db->getConnection();

        if ($dbConn === null) {
            return -1;
        }

        if (! empty($filters['reifier'])) {
            // TODO to be implemented
            return -1;
        }

        if (! isset($filters['topic'])) {
            return -1;
        }

        $query = 'MATCH (t:Topic { id: {id} })-[:hasOccurrence]->(node:Occurrence) RETURN node';
        $bind = ['id' => $filters['topic']];

        $logger->info($query, $bind);

        try {
            $qResult = $dbConn->run($query, $bind);
        } catch (Neo4jException $exception) {
            $logger->error($exception->getMessage());

            // TODO: Error handling
            return -1;
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
                    'reifier' => ($node->hasValue('reifier') ? $node->value('reifier') : false)
                ];

            // Type

            $types = array_values(array_diff($node->labels(), ['Occurrence']));
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


    public function insertAll($topicId, array $data, Transaction $transaction)
    {
        foreach ($data as $occurrenceData) {
            $this->insertOccurrence($topicId, $occurrenceData, $transaction);
        }

        // TODO: error handling

        return 1;
    }


    public function updateAll($topicId, array $data, array $previousData, Transaction $transaction)
    {
        $ok = 0;
        $previousOccurrenceData = [];

        foreach ($data as $occurrenceData) {
            // No ID? Must be a new occurrence

            if (empty($occurrenceData['id'])) {
                $ok = $this->insertOccurrence($topicId, $occurrenceData, $transaction);

                if ($ok < 0) {
                    return $ok;
                }

                continue;
            }

            // If the ID is not in $previous_data, it's a new occurrence

            $found = false;

            foreach ($previousData as $previousOccurrenceData) {
                if ($previousOccurrenceData['id'] === $occurrenceData['id']) {
                    $found = true;
                    break;
                }
            }

            if (! $found) {
                $ok = $this->insertOccurrence($topicId, $occurrenceData, $transaction);

                if ($ok < 0) {
                    return $ok;
                }

                continue;
            }

            // It's an updated occurrence...

            $ok = $this->updateOccurrence($topicId, $occurrenceData, $previousOccurrenceData, $transaction);

            if ($ok < 0) {
                return $ok;
            }

            // TODO: handle occurrence deletion, or empty value
        }

        // TODO: error handling
        return $ok;
    }


    protected function insertOccurrence($topicId, array $data, Transaction $transaction)
    {
        $logger = $this->topicMap->getLogger();

        if ((! isset($data['value'])) || (strlen($data['value']) === 0)) {
            return 0;
        }

        if (empty($data['id'])) {
            $data['id'] = $this->topicMap->createId();
        }

        if (empty($data['scope'])) {
            $data['scope'] = [];
        } elseif (! is_array($data['scope'])) {
            $data['scope'] = [$data['scope']];
        }

        if (! isset($data['language'])) {
            $data['language'] = false;
        }

        if (! isset($data['reifier'])) {
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

        $classes = ['Occurrence', $data['type']];

        $query = sprintf
        (
            'MATCH (a:Topic { id: {topic_id} })'
            . ' CREATE (a)-[:hasOccurrence]->(b%s { %s })',
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
            TopicMapInterface::SUBJECT_OCCURRENCE_TYPE
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

        foreach ($typeQueries as $query) {
            $logger->info($query['query'], $query['bind']);
            $transaction->push($query['query'], $query['bind']);
        }

        // Link reifier

        if (strlen($data['reifier']) > 0) {
            $reifierQueries = DbUtils::tmConstructLinkReifierQueries
            (
                TopicInterface::REIFIES_OCCURRENCE,
                $data['id'],
                $data['reifier']
            );

            foreach ($reifierQueries as $query) {
                $logger->info($query['query'], $query['bind']);
                $transaction->push($query['query'], $query['bind']);
            }
        }

        // TODO: error handling
        return 1;
    }


    protected function updateOccurrence($topicId, array $data, array $previousData, Transaction $transaction)
    {
        $logger = $this->topicMap->getLogger();

        if ((! isset($data['value'])) || (strlen($data['value']) === 0)) {
            $bind = ['id' => $data['id']];
            $query = 'MATCH (node:Occurrence { id: {id} }) OPTIONAL MATCH (node)-[r:hasOccurrence]-() DELETE r, node';

            $logger->info($query, $bind);

            $transaction->push($query, $bind);

            // TODO: error handling
            return 1;
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
            'MATCH (node:Occurrence { id: {id} })%s',
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
                TopicMapInterface::SUBJECT_OCCURRENCE_TYPE
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
                    TopicInterface::REIFIES_OCCURRENCE,
                    $data['id'],
                    $data['reifier']
                );
            } else {
                $reifierQueries = DbUtils::tmConstructUnlinkReifierQueries
                (
                    TopicInterface::REIFIES_OCCURRENCE,
                    $data['id'],
                    $previousData['reifier']
                );
            }

            foreach ($reifierQueries as $query) {
                $logger->info($query['query'], $query['bind']);
                $transaction->push($query['query'], $query['bind']);
            }
        }

        // TODO: error handling
        return 1;
    }
}
