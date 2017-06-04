<?php

namespace TopicCards\Model;

use TopicCards\Exception\TopicCardsLogicException;
use TopicCards\Exception\TopicCardsRuntimeException;
use TopicCards\Interfaces\PersistentDbAdapterInterface;
use TopicCards\Interfaces\PersistentSearchAdapterInterface;
use TopicCards\Interfaces\TopicMapInterface;


trait PersistentTrait
{
    /** @var string */
    protected $created = '';

    /** @var string */
    protected $updated = '';

    /** @var int */
    protected $version = 0;

    /** @var bool */
    protected $loaded = false;

    /** @var array Copy of the data as it was on load (needed for label removal) */
    protected $previousData = [];


    /**
     * @return string ISO datetime
     */
    public function getCreated()
    {
        return $this->created;
    }


    /**
     * @param string $date ISO datetime
     * @return self
     */
    public function setCreated($date)
    {
        $this->created = $date;

        return $this;
    }


    /**
     * @return string ISO datetime
     */
    public function getUpdated()
    {
        return $this->updated;
    }


    /**
     * @param string $date ISO datetime
     * @return self
     */
    public function setUpdated($date)
    {
        $this->updated = $date;

        return $this;
    }


    /**
     * @return int
     */
    public function getVersion()
    {
        return $this->version;
    }


    /**
     * @param int $version
     * @return self
     */
    public function setVersion($version)
    {
        $this->version = intval($version);

        return $this;
    }


    /**
     * @return array
     */
    public function getAllPersistent()
    {
        return
            [
                'created' => $this->getCreated(),
                'updated' => $this->getUpdated(),
                'version' => $this->getVersion()
            ];
    }


    /**
     * @param array $data
     * @return self
     */
    public function setAllPersistent(array $data)
    {
        $data = array_merge(
            [
                'created' => false,
                'updated' => false,
                'version' => 0
            ], $data);

        $this->setCreated($data['created']);
        $this->setUpdated($data['updated']);
        $this->setVersion($data['version']);

        return $this;
    }


    /**
     * @return PersistentDbAdapterInterface
     */
    protected function getPersistentDbAdapter()
    {
        return $this->dbAdapter;
    }


    /**
     * @param string $id
     * @return bool
     */
    public function load($id)
    {
        $this->previousData = [];

        $rows = $this->getPersistentDbAdapter()->selectAll(['id' => $id]);

        if (count($rows) === 0) {
            return false;
        }

        $this->setAll($rows[0]);

        $this->previousData = $this->getAll();
        $this->loaded = true;

        return $this->loaded;
    }


    /**
     * @return bool
     */
    public function isLoaded()
    {
        return $this->loaded;
    }


    /**
     * @throws TopicCardsRuntimeException
     * @return void
     */
    public function save()
    {
        /** @var TopicMapInterface $topicMap */
        $topicMap = $this->getTopicMap();

        /** @var PersistentSearchAdapterInterface $searchAdapter */
        $searchAdapter = $this->getSearchAdapter();

        $ok = $this->validate($msgHtml);

        if ($ok < 0) {
            $errorMsg = sprintf
            (
                '%s (%s): <%s> save cancelled because the validation failed (<%s> %s).',
                __METHOD__,
                get_class($this),
                $this->getId(),
                $msgHtml,
                $ok
            );

            throw new TopicCardsRuntimeException($errorMsg, $ok);
        }

        $searchAdapter->resetIndexRelated();

        if ($this->getVersion() === 0) {
            if (strlen($this->getId()) === 0) {
                $this->setId($topicMap->createId());
            }

            try {
                $this->getPersistentDbAdapter()->insertAll($this->getAll());
            } catch (\Exception $exception) {
                throw new TopicCardsRuntimeException
                (
                    sprintf
                    (
                        '%s: %s <%s> insert failed.',
                        __METHOD__, get_class($this), $this->getId()
                    ),
                    0,
                    $exception
                );
            }
        } else {
            try {
                $this->getPersistentDbAdapter()->updateAll($this->getAll());
            } catch (\Exception $exception) {
                throw new TopicCardsRuntimeException
                (
                    sprintf
                    (
                        '%s: %s <%s> update failed.',
                        __METHOD__, get_class($this), $this->getId()
                    ),
                    0,
                    $exception
                );
            }
        }

        if ($ok >= 0) {
            $this->setVersion($this->getVersion() + 1);
            $this->previousData = $this->getAll();

            $searchAdapter->index();
            $searchAdapter->indexRelated();

            $this->addHistoryItem(($this->getVersion() <= 1 ? 'i' : 'u'));
        }
    }


    /**
     * @return void
     */
    public function delete()
    {
        if ($this->getVersion() === 0) {
            throw new TopicCardsLogicException
            (
                sprintf
                (
                    '%s: Cannot delete an object that has not been saved yet.',
                    __METHOD__
                )
            );
        }

        $this->getSearchAdapter()->removeFromIndex();

        $this->getSearchAdapter()->resetIndexRelated();

        try {
            $this->getPersistentDbAdapter()->deleteById($this->getId(), $this->getVersion());
        } catch (\Exception $exception) {
            // Sort of manual rollback: If deletion failed, re-add to index
            $this->getSearchAdapter()->index();
            return;
        }
        
        $this->getSearchAdapter()->indexRelated();
        $this->addHistoryItem('d');
    }


    /**
     * @return array
     */
    public function getPreviousData()
    {
        return $this->previousData;
    }


    /**
     * @param string $dmlType
     * @return int
     */
    protected function addHistoryItem($dmlType)
    {
        /** @var TopicMapInterface $topicMap */
        $topicMap = $this->getTopicMap();

        /** @var PersistentSearchAdapterInterface $searchAdapter */
        $searchAdapter = $this->getSearchAdapter();

        $topicMap->getSearch()->index
        (
            [
                'type' => 'history',
                'body' =>
                    [
                        'type' => $searchAdapter->getSearchType(),
                        'id' => $this->getId(),
                        'when' => date('c'),
                        'dml' => $dmlType
                    ]
            ]
        );

        return 1;
    }


    /**
     * @return array
     */
    public function getHistoryItems()
    {
        /** @var TopicMapInterface $topicMap */
        $topicMap = $this->getTopicMap();

        $result = [];

        $query =
            [
                'query' => ['filtered' => ['filter' => ['term' => ['id' => $this->getId()]]]],
                'size' => 50,
                'from' => 0
            ];

        $response = $topicMap->getSearch()->search
        (
            [
                'type' => 'history',
                'body' => $query
            ]
        );

        if (empty($response['hits']['hits'])) {
            return $result;
        }

        foreach ($response['hits']['hits'] as $hit) {
            $result[] = $hit['_source'];
        }

        return $result;
    }
}
