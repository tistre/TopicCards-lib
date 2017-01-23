<?php

namespace TopicCards\Interfaces;

use GraphAware\Neo4j\Client\Transaction\Transaction;


interface RoleDbAdapterInterface
{
    public function selectAll(array $filters);


    public function insertAll($associationId, array $data, Transaction $transaction);


    public function updateAll($associationId, array $data, array $previousData, Transaction $transaction);
}
