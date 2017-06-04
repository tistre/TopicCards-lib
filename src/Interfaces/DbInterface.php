<?php

namespace TopicCards\Interfaces;

use GraphAware\Neo4j\Client\ClientInterface;
use GraphAware\Neo4j\Client\Transaction\Transaction;


interface DbInterface
{
    /**
     * DbInterface constructor.
     *
     * @param array $params
     */
    public function __construct(array $params);


    /**
     * @return array
     */
    public function getParams();


    /**
     * @return ClientInterface
     */
    public function getConnection();


    /**
     * @return Transaction
     */
    public function beginTransaction();


    /**
     * @param Transaction $transaction
     * @return int
     */
    public function commit(Transaction $transaction);


    /**
     * @param Transaction $transaction
     * @return int
     */
    public function rollBack(Transaction $transaction);
}
