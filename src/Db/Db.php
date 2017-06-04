<?php

namespace TopicCards\Db;

use GraphAware\Common\Transaction\TransactionInterface;
use GraphAware\Neo4j\Client\ClientBuilder;
use GraphAware\Neo4j\Client\ClientInterface;
use GraphAware\Neo4j\Client\Transaction\Transaction;
use TopicCards\Exception\TopicCardsLogicException;
use TopicCards\Interfaces\DbInterface;


class Db implements DbInterface
{
    /** @var array */
    protected $params = [];

    /** @var ClientInterface|bool */
    protected $connection = false;

    /** @var int */
    protected $transactionLevel = 0;

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

        $this->transactionLevel++;

        if ($this->transactionLevel === 1) {
            $this->getConnection();

            $this->transaction = $this->connection->transaction();
        }

        return $this->transaction;
    }


    /**
     * @param Transaction $transaction
     * @return void
     */
    public function commit(Transaction $transaction)
    {
        if ($this->transactionLevel <= 0) {
            throw new TopicCardsLogicException
            (
                sprintf
                (
                    '%s: Transaction level is less than zero (%s).',
                    __METHOD__,$this->transactionLevel
                )
            );
        }

        $this->transactionLevel--;

        if ($this->transactionLevel > 0) {
            return;
        }

        $transaction->commit();

        // We intentionally don't reset $this->transaction here
        // since the caller might still want to read from the transaction
    }


    /**
     * @param Transaction $transaction
     * @return void
     */
    public function rollBack(Transaction $transaction)
    {
        if ($this->transactionLevel <= 0) {
            throw new TopicCardsLogicException
            (
                sprintf
                (
                    '%s: Transaction level is less than zero (%s).', 
                    __METHOD__,$this->transactionLevel
                )
            );
        }

        $transaction->rollback();
        $this->transactionLevel = 0;

        // Running into "RuntimeException: A transaction is already bound to this session",
        // trying to work around it by reconnecting

        $this->connection = false;
        $this->getConnection();
    }
}
