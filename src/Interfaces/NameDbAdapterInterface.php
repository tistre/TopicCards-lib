<?php

namespace TopicCards\Interfaces;

use GraphAware\Neo4j\Client\Transaction\Transaction;


interface NameDbAdapterInterface
{
    /**
     * @param array $filters
     * @return array|int Negative number on error
     */
    public function selectAll(array $filters);


    /**
     * @param string $topic_id
     * @param array $data
     * @param Transaction $transaction
     * @return int
     */
    public function insertAll($topic_id, array $data, Transaction $transaction);


    /**
     * @param string $topic_id
     * @param array $data
     * @param array $previous_data
     * @param Transaction $transaction
     * @return int
     */
    public function updateAll($topic_id, array $data, array $previous_data, Transaction $transaction);
}
