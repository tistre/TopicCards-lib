<?php

namespace TopicCards\Model;

use Psr\Log\LoggerInterface;
use TopicCards\Db\TopicMapDbAdapter;
use TopicCards\Interfaces\DbInterface;
use TopicCards\Interfaces\SearchInterface;
use TopicCards\Interfaces\TopicMapInterface;
use TopicCards\Interfaces\TopicMapDbAdapterInterface;
use TopicCards\Utils\StringUtils;


class TopicMap implements TopicMapInterface
{
    protected $url;
    protected $listeners = [];
    protected $searchIndex;
    protected $cache = [];

    /** @var LoggerInterface */
    protected $logger;

    /** @var SearchInterface */
    protected $search;

    /** @var DbInterface */
    protected $db;

    /** @var TopicMapDbAdapterInterface */
    protected $dbAdapter;


    public function __construct()
    {
        $this->dbAdapter = new TopicMapDbAdapter($this);
    }


    /**
     * @param LoggerInterface $logger
     * @return mixed
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }


    /**
     * @return LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }


    /**
     * @param SearchInterface $search
     * @return mixed
     */
    public function setSearch(SearchInterface $search)
    {
        $this->search = $search;
    }


    /**
     * @return SearchInterface
     */
    public function getSearch()
    {
        return $this->search;
    }


    /**
     * @param DbInterface $db
     * @return mixed
     */
    public function setDb(DbInterface $db)
    {
        $this->db = $db;
    }


    /**
     * @return DbInterface
     */
    public function getDb()
    {
        return $this->db;
    }


    public function on($event, callable $callback)
    {
        if (! isset($this->listeners[$event])) {
            $this->listeners[$event] = [];
        }

        $this->listeners[$event][] = $callback;

        return 1;
    }


    public function trigger($event, array $params, array &$result)
    {
        if (! isset($this->listeners[$event])) {
            return 0;
        }

        $cnt = 0;

        foreach ($this->listeners[$event] as $callback) {
            $callbackOk = $callback($this, $event, $params, $result);

            if ($callbackOk < 0) {
                return $callbackOk;
            }

            $cnt++;
        }

        return $cnt;
    }


    public function setUrl($url)
    {
        $this->url = $url;

        return 1;
    }


    public function getUrl()
    {
        return $this->url;
    }


    public function setSearchIndex($index)
    {
        $this->searchIndex = $index;

        return 1;
    }


    public function getSearchIndex()
    {
        return $this->searchIndex;
    }


    public function getReifierId()
    {
        return $this->getTopicIdBySubject($this->getUrl());
    }


    public function createId()
    {
        return StringUtils::generateUuid();
    }


    /**
     * @return Topic
     */
    public function newTopic()
    {
        $topic = new Topic($this);

        return $topic;
    }


    public function getTopicIds(array $filters)
    {
        return $this->dbAdapter->selectTopics($filters);
    }


    public function clearCache()
    {
        $this->cache = [];
    }
    

    public function getTopicIdBySubject($uri, $createTopic = false)
    {
        $cacheKey = __METHOD__ . "($uri)";

        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $result = $this->dbAdapter->selectTopicBySubject($uri);

        if ((strlen($result) === 0) && $createTopic) {
            $topic = $this->newTopic();
            $topic->setSubjectIdentifiers([$uri]);
            $ok = $topic->save();

            if ($ok >= 0) {
                $result = $topic->getId();
            }
        }

        if (strlen($result) > 0) {
            $this->cache[$cacheKey] = $result;
        }

        return $result;
    }


    public function getTopicSubject($topicId)
    {
        $cacheKey = __METHOD__ . "($topicId)";

        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        // XXX we might want to optimize this and not do 2 calls
        // to get at the locator

        $result = $this->dbAdapter->selectTopicSubjectIdentifier($topicId);

        if ($result === false) {
            $result = $this->dbAdapter->selectTopicSubjectLocator($topicId);
        }

        $this->cache[$cacheKey] = $result;

        return $result;
    }


    public function getTopicSubjectIdentifier($topicId)
    {
        if (strlen($topicId) === 0) {
            return false;
        }

        $cacheKey = __METHOD__ . "($topicId)";

        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $result = $this->dbAdapter->selectTopicSubjectIdentifier($topicId);

        $this->cache[$cacheKey] = $result;

        return $result;
    }


    public function getTopicSubjectLocator($topicId)
    {
        if (strlen($topicId) === 0) {
            return false;
        }

        $cacheKey = __METHOD__ . "($topicId)";

        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $result = $this->dbAdapter->selectTopicSubjectLocator($topicId);

        $this->cache[$cacheKey] = $result;

        return $result;
    }


    public function getTopicLabel($topicId)
    {
        if (strlen($topicId) === 0) {
            return false;
        }

        $cacheKey = __METHOD__ . "($topicId)";

        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $topic = $this->newTopic();

        $ok = $topic->load($topicId);

        if ($ok < 0) {
            return false;
        }

        $result = $topic->getLabel();

        $this->cache[$cacheKey] = $result;

        return $result;
    }


    public function newAssociation()
    {
        $association = new Association($this);

        return $association;
    }


    public function getAssociationIds(array $filters)
    {
        return $this->dbAdapter->selectAssociations($filters);
    }


    public function getTopicTypeIds(array $filters)
    {
        return $this->dbAdapter->selectTopicTypes($filters);
    }


    public function getNameTypeIds(array $filters)
    {
        return $this->dbAdapter->selectNameTypes($filters);
    }


    public function getNameScopeIds(array $filters)
    {
        return $this->dbAdapter->selectNameScopes($filters);
    }


    public function getOccurrenceTypeIds(array $filters)
    {
        return $this->dbAdapter->selectOccurrenceTypes($filters);
    }


    public function getOccurrenceDataTypeIds(array $filters)
    {
        return $this->dbAdapter->selectOccurrenceDataTypes($filters);
    }


    public function getOccurrenceScopeIds(array $filters)
    {
        return $this->dbAdapter->selectOccurrenceScopes($filters);
    }


    public function getAssociationTypeIds(array $filters)
    {
        return $this->dbAdapter->selectAssociationTypes($filters);
    }


    public function getAssociationScopeIds(array $filters)
    {
        return $this->dbAdapter->selectAssociationScopes($filters);
    }


    public function getRoleTypeIds(array $filters)
    {
        return $this->dbAdapter->selectRoleTypes($filters);
    }


    public function getRolePlayerIds(array $filters)
    {
        return $this->dbAdapter->selectRolePlayers($filters);
    }


    // TODO Move into Utils?
    public function newFileTopic($filename)
    {
        $topic = $this->newTopic();

        $name = $topic->newName();

        $name->setType('http://dbpedia.org/ontology/filename');
        $name->setValue(pathinfo($filename, PATHINFO_BASENAME));

        $topic->setSubjectLocators(['file://' . $filename]);

        $occurrence = $topic->newOccurrence();
        $occurrence->setType('http://schema.org/contentSize');
        $occurrence->setDataType('http://www.w3.org/2001/XMLSchema#nonNegativeInteger');
        $occurrence->setValue(filesize($filename));

        $occurrence = $topic->newOccurrence();
        $occurrence->setType('http://purl.uniprot.org/core/md5Checksum');
        $occurrence->setDataType('http://www.w3.org/2001/XMLSchema#string');
        $occurrence->setValue(md5_file($filename));

        $type = 'http://dbpedia.org/ontology/File';

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimetype = finfo_file($finfo, $filename);
        finfo_close($finfo);

        if (strlen($mimetype) > 0) {
            $occurrence = $topic->newOccurrence();
            $occurrence->setType('http://www.w3.org/ns/dcat#mediaType');
            $occurrence->setDataType('http://www.w3.org/2001/XMLSchema#string');
            $occurrence->setValue($mimetype);

            if (substr($mimetype, 0, 6) === 'image/') {
                $type = 'http://schema.org/ImageObject';
            }
        }

        $topic->setTypes([$type]);

        $size = getimagesize($filename);

        if (is_array($size)) {
            $occurrence = $topic->newOccurrence();
            $occurrence->setType('http://schema.org/width');
            $occurrence->setDataType('http://www.w3.org/2001/XMLSchema#nonNegativeInteger');
            $occurrence->setValue($size[0]);

            $occurrence = $topic->newOccurrence();
            $occurrence->setType('http://schema.org/height');
            $occurrence->setDataType('http://www.w3.org/2001/XMLSchema#nonNegativeInteger');
            $occurrence->setValue($size[1]);
        }

        return $topic;
    }


    public function getPreferredLabelScopes()
    {
        // TODO to be implemented
        return [[], '*'];
    }
}
