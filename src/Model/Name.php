<?php

namespace TopicCards\Model;

use TopicCards\Db\NameDbAdapter;
use TopicCards\Interfaces\iName;
use TopicCards\Interfaces\iNameDbAdapter;
use TopicCards\Interfaces\iTopicMap;


class Name extends Core implements iName
{
    use Reified, Scoped, Typed;
    
    protected $value = false;
    
    /** @var iNameDbAdapter */
    protected $db_adapter;


    /**
     * Name constructor.
     *
     * @param iTopicMap $topicmap
     */
    public function __construct(iTopicMap $topicmap)
    {
        parent::__construct($topicmap);

        $this->db_adapter = new NameDbAdapter($this);
    }


    /**
     * @return iNameDbAdapter
     */
    public function getDbAdapter()
    {
        return $this->db_adapter;
    }


    public function getValue()
    {
        return $this->value;
    }
    
    
    public function setValue($str)
    {
        $this->value = $str;
        return 1;
    }
    
    
    public function getAll()
    {
        $result = 
        [
            'value' => $this->getValue()
        ];

        $result = array_merge($result, $this->getAllId());

        $result = array_merge($result, $this->getAllTyped());

        $result = array_merge($result, $this->getAllReified());

        $result = array_merge($result, $this->getAllScoped());
            
        return $result;
    }
    
    
    public function setAll(array $data)
    {
        $data = array_merge(
        [
            'value' => false
        ], $data);
        
        $ok = $this->setValue($data[ 'value' ]);
        
        if ($ok >= 0)
            $ok = $this->setAllId($data);
            
        if ($ok >= 0)
            $ok = $this->setAllTyped($data);
            
        if ($ok >= 0)
            $ok = $this->setAllReified($data);
            
        if ($ok >= 0)
            $ok = $this->setAllScoped($data);
            
        return $ok;
    }


    /**
     * Mark an existing (saved) name for removal on topic save
     */
    public function remove()
    {
        $this->setValue('');
    }
}
