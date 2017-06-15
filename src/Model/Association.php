<?php

namespace TopicCards\Model;

use TopicCards\Db\AssociationDbAdapter;
use TopicCards\Interfaces\AssociationInterface;
use TopicCards\Interfaces\AssociationDbAdapterInterface;
use TopicCards\Interfaces\PersistentSearchAdapterInterface;
use TopicCards\Interfaces\RoleInterface;
use TopicCards\Interfaces\TopicMapInterface;
use TopicCards\Interfaces\TypedInterface;
use TopicCards\Search\AssociationSearchAdapter;


class Association extends Core implements AssociationInterface
{
    use PersistentTrait, ReifiedTrait, ScopedTrait, TypedTrait;
    
    /** @var RoleInterface[] */
    protected $roles = [ ];
    
    /** @var AssociationDbAdapterInterface */
    protected $dbAdapter;

    /** @var PersistentSearchAdapterInterface */
    protected $search_adapter;

    
    /**
     * Association constructor.
     *
     * @param TopicMapInterface $topicMap
     */
    public function __construct(TopicMapInterface $topicMap)
    {
        parent::__construct($topicMap);

        $this->dbAdapter = new AssociationDbAdapter($this);
        $this->search_adapter = new AssociationSearchAdapter($this);
    }


    /**
     * @return AssociationDbAdapterInterface
     */
    public function getDbAdapter()
    {
        return $this->dbAdapter;
    }


    /**
     * @return PersistentSearchAdapterInterface
     */
    public function getSearchAdapter()
    {
        return $this->search_adapter;
    }


    /**
     * @return string
     */
    public function getIdentifier()
    {
        return $this->topicMap->generateIdentifier('association', $this->getId());
    }


    /**
     * @return RoleInterface
     */
    public function newRole()
    {   
        $role = new Role($this->topicMap);
        
        $this->roles[ ] = $role;
        
        return $role;
    }


    /**
     * @param array $filters
     * @return RoleInterface[]
     */
    public function getRoles(array $filters = [ ])
    {
        if (count($filters) === 0)            
            return $this->roles;
        
        $result = [ ];
        
        if (isset($filters[ 'type' ]))
            $filters[ 'type_id' ] = $this->topicMap->getTopicIdBySubject($filters[ 'type' ]);

        if (isset($filters[ 'player' ]))
            $filters[ 'player_id' ] = $this->topicMap->getTopicIdBySubject($filters[ 'player' ]);

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


    /**
     * @param RoleInterface[] $roles
     * @return self
     */
    public function setRoles(array $roles)
    {
        $this->roles = $roles;
        return $this;
    }


    /**
     * @param array $filters
     * @return RoleInterface
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

    
    public function validate(&$msgHtml)
    {
        $result = 1;
        $msgHtml = '';
        
        if (strlen($this->getTypeId()) === 0)
        {
            $result = TypedInterface::ERR_TYPE_MISSING;
            $msgHtml .= 'Missing association type.';
        }
        
        foreach ($this->getRoles([ ]) as $role)
        {
            $ok = $role->validate($msg);
            
            if ($ok < 0)
            {
                $result = $ok;
                $msgHtml .= $msg;
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


    /**
     * @param array $data
     * @return self
     */
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
        
        return $this;
    }
}
