<?php

namespace TopicCards\Model;

use TopicCards\Db\RoleDbAdapter;
use TopicCards\Interfaces\RoleInterface;
use TopicCards\Interfaces\RoleDbAdapterInterface;
use TopicCards\Interfaces\TopicMapInterface;
use TopicCards\Interfaces\TypedInterface;


class Role extends Core implements RoleInterface 
{
    // TODO use , RoleDbAdapter
    use Reified, Typed;
    
    protected $player = false;


    /** @var RoleDbAdapterInterface */
    protected $db_adapter;


    /**
     * Name constructor.
     *
     * @param TopicMapInterface $topicmap
     */
    public function __construct(TopicMapInterface $topicmap)
    {
        parent::__construct($topicmap);

        $this->db_adapter = new RoleDbAdapter($this);
    }


    /**
     * @return RoleDbAdapterInterface
     */
    public function getDbAdapter()
    {
        return $this->db_adapter;
    }


    public function getPlayerId()
    {
        return $this->player;
    }
    
    
    public function setPlayerId($topic_id)
    {
        $this->player = $topic_id;
        return 1;
    }


    public function getPlayer()
    {
        return $this->topicmap->getTopicSubject($this->getPlayerId());
    }


    public function setPlayer($topic_subject)
    {
        $topic_id = $this->topicmap->getTopicIdBySubject($topic_subject, true);
        
        if (strlen($topic_id) === 0)
        {
            return -1;
        }
            
        return $this->setPlayerId($topic_id);
    }
    
    
    public function getAll()
    {
        $result =
        [
            'player' => $this->getPlayerId()
        ];
        
        $result = array_merge($result, $this->getAllId());

        $result = array_merge($result, $this->getAllTyped());

        $result = array_merge($result, $this->getAllReified());
            
        return $result;
    }
    
    
    public function setAll(array $data)
    {
        $data = array_merge(
        [
            'player' => false
        ], $data);
        
        $ok = $this->setPlayerId($data[ 'player' ]);
        
        if ($ok >= 0)
            $ok = $this->setAllId($data);
            
        if ($ok >= 0)
            $ok = $this->setAllTyped($data);
            
        if ($ok >= 0)
            $ok = $this->setAllReified($data);
            
        return $ok;
    }


    /**
     * Mark an existing (saved) role for removal on association save
     */
    public function remove()
    {
        $this->setPlayerId('');
    }
    
    
    public function validate(&$msg_html)
    {
        $result = 1;

        if (strlen($this->getTypeId()) === 0)
        {
            $result = TypedInterface::ERR_TYPE_MISSING;
            $msg_html .= 'Missing role type.';
        }

        return $result;
    }
}
