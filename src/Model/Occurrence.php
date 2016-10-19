<?php

namespace TopicCards\Model;

use TopicCards\Db\OccurrenceDbAdapter;
use TopicCards\Interfaces\iOccurrence;
use TopicCards\Interfaces\iOccurrenceDbAdapter;
use TopicCards\Interfaces\iTopicMap;
use TopicCards\Utils\DatatypeUtils;


class Occurrence extends Core implements iOccurrence
{
    // TODO use OccurrenceDbAdapter
    use Reified, Scoped, Typed;
    
    protected $value = false;
    protected $datatype = false;


    /** @var iOccurrenceDbAdapter */
    protected $db_adapter;


    /**
     * Name constructor.
     *
     * @param iTopicMap $topicmap
     */
    public function __construct(iTopicMap $topicmap)
    {
        parent::__construct($topicmap);

        $this->db_adapter = new OccurrenceDbAdapter($this);
    }


    /**
     * @return iOccurrenceDbAdapter
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
    
    
    public function getDatatypeId()
    {
        return $this->datatype;
    }
    

    public function setDatatypeId($topic_id)
    {
        $this->datatype = $topic_id;
        
        return 1;
    }
    

    public function getDatatype()
    {
        return $this->topicmap->getTopicSubject($this->getDatatypeId());
    }
    
    
    public function setDatatype($topic_subject)
    {
        $topic_id = $this->topicmap->getTopicIdBySubject($topic_subject, true);
        
        if (strlen($topic_id) === 0)
            return -1;
            
        return $this->setDatatypeId($topic_id);
    }

    
    public function validate(&$msg_html)
    {
        $ok = DatatypeUtils::validate
        (
            $this->value, 
            $this->getDatatype(), 
            $msg_txt
        );
        
        $msg_html = htmlspecialchars($msg_txt);
        
        return $ok;
    }

    
    public function getAll()
    {
        $result = 
        [
            'value' => $this->getValue(),
            'datatype' => $this->getDatatypeId()
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
            'value' => false,
            'datatype' => false
        ], $data);
        
        $ok = $this->setValue($data[ 'value' ]);
        $ok = $this->setDatatypeId($data[ 'datatype' ]);
        
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
     * Mark an existing (saved) occurrence for removal on topic save
     */
    public function remove()
    {
        $this->setValue('');
    }
}
