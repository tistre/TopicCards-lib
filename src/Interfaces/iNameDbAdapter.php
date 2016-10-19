<?php

namespace TopicCards\Interfaces;

use GraphAware\Neo4j\Client\Transaction\Transaction;


interface iNameDbAdapter
{
    public function selectAll(array $filters);


    public function insertAll($topic_id, array $data, Transaction $transaction);


    public function updateAll($topic_id, array $data, array $previous_data, Transaction $transaction);
}
