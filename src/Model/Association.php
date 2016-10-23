<?php

namespace TopicCards\Model;

use TopicCards\Db\AssociationDbAdapter;
use TopicCards\Interfaces\iAssociation;
use TopicCards\Interfaces\iAssociationDbAdapter;
use TopicCards\Interfaces\iPersistentSearchAdapter;
use TopicCards\Interfaces\iRole;
use TopicCards\Interfaces\iTopicMap;
use TopicCards\Search\AssociationSearchAdapter;


class Association extends Core implements iAssociation
{
    // TODO use AssociationSearchAdapter, Searchable
    use Persistent, Reified, Scoped, Typed;
    
    /** @var iRole[] */
    protected $roles = [ ];
    
    /** @var iAssociationDbAdapter */
    protected $db_adapter;

    /** @var iPersistentSearchAdapter */
    protected $search_adapter;

    
    /**
     * Association constructor.
     *
     * @param iTopicMap $topicmap
     */
    public function __construct(iTopicMap $topicmap)
    {
        parent::__construct($topicmap);

        $this->db_adapter = new AssociationDbAdapter($this);
        $this->search_adapter = new AssociationSearchAdapter($this);
    }


    /**
     * @return iAssociationDbAdapter
     */
    public function getDbAdapter()
    {
        return $this->db_adapter;
    }


    /**
     * @return iPersistentSearchAdapter
     */
    public function getSearchAdapter()
    {
        return $this->search_adapter;
    }


    /**
     * @return iRole
     */
    public function newRole()
    {   
        $role = new Role($this->topicmap);
        
        $this->roles[ ] = $role;
        
        return $role;
    }


    /**
     * @param array $filters
     * @return iRole[]
     */
    public function getRoles(array $filters = [ ])
    {
        if (count($filters) === 0)            
            return $this->roles;
        
        $result = [ ];
        
        if (isset($filters[ 'type' ]))
            $filters[ 'type_id' ] = $this->topicmap->getTopicIdBySubject($filters[ 'type' ]);

        if (isset($filters[ 'player' ]))
            $filters[ 'player_id' ] = $this->topicmap->getTopicIdBySubject($filters[ 'player' ]);

        foreach ($this->roles as $role)
        {
            $match = true;

            if (isset($filters[ 'id' ]) && ($role->getId() !== $filters[ 'id' ]))
                $match = false;

            if (isset($filters[ 'type_id' ]) && ($role->getTypeId() !== $filters[ 'type_id' ]))
                $match = false;
                
            if (isset($filters[ 'player_id' ]) && ($role->getPlayerId() !== $filters[ 'player_id' ]))
                $match = false;
                
            if ($match)
                $result[ ] = $role;
        }
        
        return $result;
    }
    
    
    public function setRoles(array $roles)
    {
        $this->roles = $roles;
        return 1;
    }


    /**
     * @param array $filters
     * @return iRole
     */
    public function getFirstRole(array $filters = [ ])
    {
        $roles = $this->getRoles($filters);
        
        if (count($roles) > 0)
            return $roles[ 0 ];

        $role = $this->newRole();
        
        if (isset($filters[ 'type' ]))
        {
            $role->setType($filters[ 'type' ]);
        }
        elseif (isset($filters[ 'type_id' ]))
        {
            $role->setTypeId($filters[ 'type_id' ]);
        }
        elseif (isset($filters[ 'id' ]))
        {
            $role->setId($filters[ 'id' ]);
        }
        
        if (isset($filters[ 'player' ]))
        {
            $role->setPlayer($filters[ 'player' ]);
        }
        elseif (isset($filters[ 'player_id' ]))
        {
            $role->setTypeId($filters[ 'player_id' ]);
        }
        
        return $role;
    }

    
    public function validate(&$msg_html)
    {
        $result = 1;
        $msg_html = '';
        
        foreach ($this->getRoles([ ]) as $role)
        {
            $ok = $role->validate($msg);
            
            if ($ok < 0)
            {
                $result = $ok;
                $msg_html .= $msg;
            }
        }
        
        return $result;
    }
    
    
    public function getAll()
    {
        $result = 
        [
            'roles' => [ ]
        ];

        foreach ($this->getRoles() as $role)
            $result[ 'roles' ][ ] = $role->getAll();
            
        $result = array_merge($result, $this->getAllId());

        $result = array_merge($result, $this->getAllPersistent());

        $result = array_merge($result, $this->getAllTyped());

        $result = array_merge($result, $this->getAllReified());

        $result = array_merge($result, $this->getAllScoped());
        
        ksort($result);

        return $result;
    }
    
    
    public function setAll(array $data)
    {
        $data = array_merge(
        [
            'roles' => [ ]
        ], $data);
        
        $this->setAllId($data);
        
        $this->setAllPersistent($data);
        
        $this->setAllTyped($data);
            
        $this->setAllReified($data);
            
        $this->setAllScoped($data);
        
        $this->setRoles([ ]);
        
        foreach ($data[ 'roles' ] as $role_data)
        {
            $role = $this->newRole();
            $role->setAll($role_data);
        }
        
        return 1;
    }
}
