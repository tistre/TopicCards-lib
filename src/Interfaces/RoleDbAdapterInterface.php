<?php

namespace TopicCards\Interfaces;

use GraphAware\Neo4j\Client\Transaction\Transaction;


interface RoleDbAdapterInterface
{
    public function selectAll(array $filters);


    public function insertAll($association_id, array $data, Transaction $transaction);


    public function updateAll($association_id, array $data, array $previous_data, Transaction $transaction);
}
