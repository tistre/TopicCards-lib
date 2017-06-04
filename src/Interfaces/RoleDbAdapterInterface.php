<?php

namespace TopicCards\Interfaces;

use GraphAware\Neo4j\Client\Transaction\Transaction;


interface RoleDbAdapterInterface
{
    /**
     * @param array $filters
     * @return array|int
     */
    public function selectAll(array $filters);


    /**
     * @param string $associationId
     * @param array $data
     * @param Transaction $transaction
     * @return int
     */
    public function insertAll($associationId, array $data, Transaction $transaction);


    /**
     * @param string $associationId
     * @param array $data
     * @param array $previousData
     * @param Transaction $transaction
     * @return int
     */
    public function updateAll($associationId, array $data, array $previousData, Transaction $transaction);
}
