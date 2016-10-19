<?php

namespace TopicCards\Interfaces;


interface iAssociation extends iPersistent, iReified, iScoped, iTyped
{
    const EVENT_SAVING = 'association_saving';
    const EVENT_DELETING = 'association_deleting';
    const EVENT_INDEXING = 'association_indexing';

    /**
     * @return iAssociationDbAdapter
     */
    public function getDbAdapter();

    /**
     * @return iPersistentSearchAdapter
     */
    public function getSearchAdapter();

    /**
     * @return iRole
     */
    public function newRole();

    /**
     * @param array $filters
     * @return iRole[]
     */
    public function getRoles(array $filters = [ ]);
    
    public function setRoles(array $roles);
    
    /**
     * @param array $filters
     * @return iRole
     */
    public function getFirstRole(array $filters = [ ]);
}
