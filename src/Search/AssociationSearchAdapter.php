<?php

namespace TopicCards\Search;

use TopicCards\Interfaces\AssociationInterface;
use TopicCards\Interfaces\TopicMapInterface;


class AssociationSearchAdapter extends PersistentSearchAdapter
{
    /** @var AssociationInterface */
    protected $association;
    

    public function __construct(AssociationInterface $association)
    {
        $this->association = $association;
        $this->topicmap = $association->getTopicMap();
    }


    public function getSearchType()
    {
        return 'association';
    }


    protected function getId()
    {
        return $this->association->getId();
    }


    protected function getIndexFields()
    {
        $result = 
        [ 
            // XXX add sort date
            'association_type_id' => $this->association->getTypeId(),
            'has_role_type_id' => [ ],
            'has_player_id' => [ ]
        ];
        
        foreach ($this->association->getRoles([ ]) as $role)
        {
            $result[ 'has_role_type_id' ][ ] = $role->getTypeId();
            $result[ 'has_player_id' ][ ] = $role->getPlayerId();
        }

        $callback_result = [ ];

        $this->topicmap->trigger
        (
            AssociationInterface::EVENT_INDEXING, 
            [ 'association' => $this->association, 'index_fields' => $result ],
            $callback_result
        );
        
        if (isset($callback_result[ 'index_fields' ]) && is_array($callback_result[ 'index_fields' ]))
            $result = $callback_result[ 'index_fields' ];
                
        return $result;
    }
}
