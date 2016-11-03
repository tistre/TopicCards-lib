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
    protected $listeners = [ ];
    protected $db_table_prefix;
    protected $search_index;
    protected $upload_path;

    /** @var LoggerInterface */
    protected $logger;

    /** @var SearchInterface */
    protected $search;
    
    /** @var DbInterface */
    protected $db;

    /** @var TopicMapDbAdapterInterface */
    protected $db_adapter;


    public function __construct()
    {
        $this->db_adapter = new TopicMapDbAdapter($this);
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
        if (! isset($this->listeners[ $event ]))
        {
            $this->listeners[ $event ] = [ ];
        }
            
        $this->listeners[ $event ][ ] = $callback;
        
        return 1;
    }
    
    
    public function trigger($event, array $params, array &$result)
    {        
        if (! isset($this->listeners[ $event ]))
        {
            return 0;
        }

        $cnt = 0;
            
        foreach ($this->listeners[ $event ] as $callback)
        {
            $callback_ok = $callback($this, $event, $params, $result);
            
            if ($callback_ok < 0)
            {
                return $callback_ok;
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
        $this->search_index = $index;
        
        return 1;
    }
    
    
    public function getSearchIndex()
    {
        return $this->search_index;
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
        return $this->db_adapter->selectTopics($filters);
    }
    

    public function getTopicIdBySubject($uri, $create_topic = false)
    {
        $result = $this->db_adapter->selectTopicBySubject($uri);
        
        if ((strlen($result) === 0) && $create_topic)
        {
            $topic = $this->newTopic();
            $topic->setSubjectIdentifiers([ $uri ]);
            $ok = $topic->save();
            
            if ($ok >= 0)
            {
                $result = $topic->getId();
            }
        }
        
        return $result;
    }
    
    
    public function getTopicSubject($topic_id)
    {
        // XXX we might want to optimize this and not do 2 calls
        // to get at the locator
        
        $result = $this->db_adapter->selectTopicSubjectIdentifier($topic_id);
        
        if ($result === false)
            $result = $this->db_adapter->selectTopicSubjectLocator($topic_id);
        
        return $result;
    }
    
    
    public function getTopicSubjectIdentifier($topic_id)
    {
        if (strlen($topic_id) === 0)
            return false;
            
        return $this->db_adapter->selectTopicSubjectIdentifier($topic_id);
    }
    
    
    public function getTopicSubjectLocator($topic_id)
    {
        if (strlen($topic_id) === 0)
            return false;
            
        return $this->db_adapter->selectTopicSubjectLocator($topic_id);
    }
    
    
    public function getTopicLabel($topic_id)
    {
        if (strlen($topic_id) === 0)
            return false;
            
        $topic = $this->newTopic();
        
        $ok = $topic->load($topic_id);
        
        if ($ok < 0)
            return false;
        
        return $topic->getLabel();
    }
    
    
    public function newAssociation()
    {
        $association = new Association($this);
        
        return $association;
    }
    
    
    public function getAssociationIds(array $filters)
    {
        return $this->db_adapter->selectAssociations($filters);
    }


    public function getTopicTypeIds(array $filters)
    {
        return $this->db_adapter->selectTopicTypes($filters);
    }


    public function getNameTypeIds(array $filters)
    {
        return $this->db_adapter->selectNameTypes($filters);
    }


    public function getNameScopeIds(array $filters)
    {
        return $this->db_adapter->selectNameScopes($filters);
    }


    public function getOccurrenceTypeIds(array $filters)
    {
        return $this->db_adapter->selectOccurrenceTypes($filters);
    }


    public function getOccurrenceDatatypeIds(array $filters)
    {
        return $this->db_adapter->selectOccurrenceDatatypes($filters);
    }


    public function getOccurrenceScopeIds(array $filters)
    {
        return $this->db_adapter->selectOccurrenceScopes($filters);
    }


    public function getAssociationTypeIds(array $filters)
    {
        return $this->db_adapter->selectAssociationTypes($filters);
    }


    public function getAssociationScopeIds(array $filters)
    {
        return $this->db_adapter->selectAssociationScopes($filters);
    }


    public function getRoleTypeIds(array $filters)
    {
        return $this->db_adapter->selectRoleTypes($filters);
    }


    public function getRolePlayerIds(array $filters)
    {
        return $this->db_adapter->selectRolePlayers($filters);
    }
    
    
    // TODO Move into Utils?
    public function newFileTopic($filename)
    {
        $topic = $this->newTopic();

        $name = $topic->newName();
        
        $name->setType('http://dbpedia.org/ontology/filename');
        $name->setValue(pathinfo($filename, PATHINFO_BASENAME));
    
        $topic->setSubjectLocators([ 'file://' . $filename ]);

        $occurrence = $topic->newOccurrence();    
        $occurrence->setType('http://schema.org/contentSize');
        $occurrence->setDatatype('http://www.w3.org/2001/XMLSchema#nonNegativeInteger');
        $occurrence->setValue(filesize($filename));

        $occurrence = $topic->newOccurrence();    
        $occurrence->setType('http://purl.uniprot.org/core/md5Checksum');
        $occurrence->setDatatype('http://www.w3.org/2001/XMLSchema#string');
        $occurrence->setValue(md5_file($filename));

        $type = 'http://dbpedia.org/ontology/File';
    
        $finfo = finfo_open(FILEINFO_MIME_TYPE);    
        $mimetype = finfo_file($finfo, $filename);
        finfo_close($finfo);

        if (strlen($mimetype) > 0)
        {
            $occurrence = $topic->newOccurrence();    
            $occurrence->setType('http://www.w3.org/ns/dcat#mediaType');
            $occurrence->setDatatype('http://www.w3.org/2001/XMLSchema#string');
            $occurrence->setValue($mimetype);
        
            if (substr($mimetype, 0, 6) === 'image/')
                $type = 'http://schema.org/ImageObject';
        }

        $topic->setTypes([ $type ]);
    
        $size = getimagesize($filename);
    
        if (is_array($size))
        {
            $occurrence = $topic->newOccurrence();    
            $occurrence->setType('http://schema.org/width');
            $occurrence->setDatatype('http://www.w3.org/2001/XMLSchema#nonNegativeInteger');
            $occurrence->setValue($size[ 0 ]);

            $occurrence = $topic->newOccurrence();    
            $occurrence->setType('http://schema.org/height');
            $occurrence->setDatatype('http://www.w3.org/2001/XMLSchema#nonNegativeInteger');
            $occurrence->setValue($size[ 1 ]);
        }
        
        return $topic;
    }


    public function getPreferredLabelScopes()
    {
        // TODO to be implemented
        return [ [ ], '*' ];
    }
}
