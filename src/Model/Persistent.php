<?php

namespace TopicCards\Model;

use TopicCards\Exception\TopicCardsException;
use TopicCards\Interfaces\iPersistentDbAdapter;
use TopicCards\Interfaces\iPersistentSearchAdapter;
use TopicCards\Interfaces\iTopicMap;


trait Persistent
{
    protected $created = false;
    protected $updated = false;
    protected $version = 0;
    protected $loaded = false;
    
    /** @var array Copy of the data as it was on load (needed for label removal) */
    protected $previous_data = [ ];
    

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
        
        $this->setCreated($data[ 'created' ]);
        $this->setUpdated($data[ 'updated' ]);
        $this->setVersion($data[ 'version' ]);
        
        return 1;
    }


    /**
     * @return iPersistentDbAdapter
     */
    protected function getPersistentDbAdapter()
    {
        return $this->db_adapter;
    }
    
    
    public function load($id)
    {
        $this->previous_data = [ ];
        
        $rows = $this->getPersistentDbAdapter()->selectAll([ 'id' => $id ]);
        
        if (! is_array($rows))
        {
            return $rows;
        }
            
        if (count($rows) === 0)
        {
            return -1;
        }
            
        $ok = $this->setAll($rows[ 0 ]);
        
        if ($ok >= 0)
        {
            $this->previous_data = $this->getAll();
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
        /** @var iTopicMap $topicmap */
        $topicmap = $this->getTopicMap();
        
        /** @var iPersistentSearchAdapter $search_adapter */
        $search_adapter = $this->getSearchAdapter();
        
        $ok = $this->validate($dummy);
        
        if ($ok < 0)
        {
            $error_msg = sprintf
            (
                '%s <%s> save cancelled because the validation failed (%s).', 
                get_class($this), 
                $this->getId(), 
                $ok
            );
            
            $topicmap->getLogger()->error($error_msg);
            throw new TopicCardsException($error_msg, $ok);
        }

        $search_adapter->resetIndexRelated();
        
        if ($this->getVersion() === 0)
        {
            if (strlen($this->getId()) === 0)
            {
                $this->setId($topicmap->createId());
            }
                
            $ok = $this->getPersistentDbAdapter()->insertAll($this->getAll());
            
            if ($ok < 0)
            {
                $topicmap->getLogger()->error(sprintf('%s <%s> save failed (%s).', get_class($this), $this->getId(), $ok));
            }
        }
        else
        {
            $ok = $this->getPersistentDbAdapter()->updateAll($this->getAll());

            if ($ok < 0)
            {
                $topicmap->getLogger()->error(sprintf('%s <%s> save failed (%s).', get_class($this), $this->getId(), $ok));
            }
        }

        if ($ok >= 0)
        {
            $this->setVersion($this->getVersion() + 1);
            $this->previous_data = $this->getAll();
            
            $search_adapter->index();
            $search_adapter->indexRelated();
            
            $this->addHistoryItem(($this->getVersion() <= 1 ? 'i' : 'u'));
        }
                
        return $ok;
    }
    
    
    public function delete()
    {
        if ($this->getVersion() === 0)
            return 0;

        $this->getSearchAdapter()->removeFromIndex();
        
        $this->getSearchAdapter()->resetIndexRelated();
        
        $ok = $this->getPersistentDbAdapter()->deleteById($this->getId(), $this->getVersion());
        
        // Sort of manual rollback: If deletion failed, re-add to index
        
        if ($ok < 0)
        {
            $this->getSearchAdapter()->index();
        }
        else
        {
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
        return $this->previous_data;
    }
    
    
    protected function addHistoryItem($dml_type)
    {
        /** @var iTopicMap $topicmap */
        $topicmap = $this->getTopicMap();
        
        /** @var iPersistentSearchAdapter $search_adapter */
        $search_adapter = $this->getSearchAdapter();
        
        $topicmap->getSearch()->index
        (
            [
                'type' => 'history',
                'body' => 
                    [
                        'type' => $search_adapter->getSearchType(),
                        'id' => $this->getId(),
                        'when' => date('c'),
                        'dml' => $dml_type
                    ]
            ]
        );
        
        return 1;
    }


    public function getHistoryItems()
    {
        /** @var iTopicMap $topicmap */
        $topicmap = $this->getTopicMap();

        $result = [ ];
        
        $query =
            [
                'query' => [ 'filtered' => [ 'filter' => [ 'term' => [ 'id' => $this->getId() ] ] ] ],
                'size' => 50,
                'from' => 0
            ];
        
        $response = $topicmap->getSearch()->search
        (
            [
                'type' => 'history',
                'body' => $query
            ]
        );

        if (empty($response[ 'hits' ][ 'hits' ]))
        {
            return $result;
        }
        
        foreach ($response[ 'hits' ][ 'hits' ] as $hit)
        {
            $result[ ] = $hit[ '_source' ];
        }
        
        return $result;
    }
}
