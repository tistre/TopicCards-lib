<?php

namespace TopicCards\Model;

use TopicCards\Exception\TopicCardsException;
use TopicCards\Interfaces\PersistentDbAdapterInterface;
use TopicCards\Interfaces\PersistentSearchAdapterInterface;
use TopicCards\Interfaces\TopicMapInterface;


trait PersistentTrait
{
    protected $created = false;
    protected $updated = false;
    protected $version = 0;
    protected $loaded = false;

    /** @var array Copy of the data as it was on load (needed for label removal) */
    protected $previousData = [];


    public function getCreated()
    {
        return $this->created;
    }


    public function setCreated($date)
    {
        $this->created = $date;

        return 1;
    }


    public function getUpdated()
    {
        return $this->updated;
    }


    public function setUpdated($date)
    {
        $this->updated = $date;

        return 1;
    }


    public function getVersion()
    {
        return $this->version;
    }


    public function setVersion($version)
    {
        $this->version = intval($version);

        return 1;
    }


    public function getAllPersistent()
    {
        return
            [
                'created' => $this->getCreated(),
                'updated' => $this->getUpdated(),
                'version' => $this->getVersion()
            ];
    }


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

        return 1;
    }


    /**
     * @return PersistentDbAdapterInterface
     */
    protected function getPersistentDbAdapter()
    {
        return $this->dbAdapter;
    }


    public function load($id)
    {
        $this->previousData = [];

        $rows = $this->getPersistentDbAdapter()->selectAll(['id' => $id]);

        if (! is_array($rows)) {
            return $rows;
        }

        if (count($rows) === 0) {
            return -1;
        }

        $ok = $this->setAll($rows[0]);

        if ($ok >= 0) {
            $this->previousData = $this->getAll();
            $this->loaded = true;
        }

        return $ok;
    }


    public function isLoaded()
    {
        return $this->loaded;
    }


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
                '%s <%s> save cancelled because the validation failed (<%s> %s).',
                get_class($this),
                $this->getId(),
                $msgHtml,
                $ok
            );

            $topicMap->getLogger()->error($errorMsg);
            throw new TopicCardsException($errorMsg, $ok);
        }

        $searchAdapter->resetIndexRelated();

        if ($this->getVersion() === 0) {
            if (strlen($this->getId()) === 0) {
                $this->setId($topicMap->createId());
            }

            $ok = $this->getPersistentDbAdapter()->insertAll($this->getAll());

            if ($ok < 0) {
                $topicMap->getLogger()->error(sprintf('%s <%s> save failed (%s).', get_class($this), $this->getId(),
                    $ok));
            }
        } else {
            $ok = $this->getPersistentDbAdapter()->updateAll($this->getAll());

            if ($ok < 0) {
                $topicMap->getLogger()->error(sprintf('%s <%s> save failed (%s).', get_class($this), $this->getId(),
                    $ok));
            }
        }

        if ($ok >= 0) {
            $this->setVersion($this->getVersion() + 1);
            $this->previousData = $this->getAll();

            $searchAdapter->index();
            $searchAdapter->indexRelated();

            $this->addHistoryItem(($this->getVersion() <= 1 ? 'i' : 'u'));
        }

        return $ok;
    }


    public function delete()
    {
        if ($this->getVersion() === 0) {
            return 0;
        }

        $this->getSearchAdapter()->removeFromIndex();

        $this->getSearchAdapter()->resetIndexRelated();

        $ok = $this->getPersistentDbAdapter()->deleteById($this->getId(), $this->getVersion());

        // Sort of manual rollback: If deletion failed, re-add to index

        if ($ok < 0) {
            $this->getSearchAdapter()->index();
        } else {
            $this->getSearchAdapter()->indexRelated();
            $this->addHistoryItem('d');
        }

        return $ok;
    }


    /**
     * @return array
     */
    public function getPreviousData()
    {
        return $this->previousData;
    }


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
