<?php

namespace TopicCards\Db;

use GraphAware\Common\Transaction\TransactionInterface;
use GraphAware\Neo4j\Client\ClientBuilder;
use GraphAware\Neo4j\Client\ClientInterface;
use GraphAware\Neo4j\Client\Transaction\Transaction;
use TopicCards\Interfaces\DbInterface;


class Db implements DbInterface
{
    /** @var array */
    protected $params = [];

    /** @var ClientInterface|bool */
    protected $connection = false;

    /** @var int */
    protected $transaction_level = 0;

    /** @var TransactionInterface */
    protected $transaction;


    public function __construct(array $params)
    {
        $this->params = $params;
    }


    public function getParams()
    {
        return $this->params;
    }


    /**
     * @return ClientInterface
     */
    public function getConnection()
    {
        if ($this->connection === false) {
            $builder = ClientBuilder::create();

            foreach ($this->params['connections'] as $key => $url) {
                $builder->addConnection($key, $url);
            }

            $this->connection = $builder->build();
        }

        return $this->connection;
    }


    /**
     * @return Transaction
     */
    public function beginTransaction()
    {
        // Wrapping Neo4j driver transaction functionality because it
        // doesn't support nested transactions

        $this->transaction_level++;

        if ($this->transaction_level === 1) {
            $this->getConnection();

            $this->transaction = $this->connection->transaction();
        }

        return $this->transaction;
    }


    /**
     * @param Transaction $transaction
     * @return int
     */
    public function commit(Transaction $transaction)
    {
        if ($this->transaction_level <= 0) {
            return -1;
        }

        $this->transaction_level--;

        if ($this->transaction_level > 0) {
            return 0;
        }

        $transaction->commit();

        // We intentionally don't reset $this->transaction here
        // since the caller might still want to read from the transaction

        return 1;
    }


    /**
     * @param Transaction $transaction
     * @return int
     */
    public function rollBack(Transaction $transaction)
    {
        if ($this->transaction_level <= 0) {
            return -1;
        }

        $transaction->rollback();
        $this->transaction_level = 0;

        // Running into "RuntimeException: A transaction is already bound to this session",
        // trying to work around it by reconnecting

        $this->connection = false;
        $this->getConnection();

        return -1;
    }
}
