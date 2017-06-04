<?php

namespace TopicCards\Interfaces;


interface AssociationInterface extends PersistentInterface, ReifiedInterface, ScopedInterface, TypedInterface
{
    const EVENT_SAVING = 'association_saving';
    const EVENT_DELETING = 'association_deleting';
    const EVENT_INDEXING = 'association_indexing';


    /**
     * @return AssociationDbAdapterInterface
     */
    public function getDbAdapter();


    /**
     * @return PersistentSearchAdapterInterface
     */
    public function getSearchAdapter();


    /**
     * @return RoleInterface
     */
    public function newRole();


    /**
     * @param array $filters
     * @return RoleInterface[]
     */
    public function getRoles(array $filters = []);


    /**
     * @param RoleInterface[] $roles
     * @return self
     */
    public function setRoles(array $roles);


    /**
     * @param array $filters
     * @return RoleInterface
     */
    public function getFirstRole(array $filters = []);
}
