<?php

namespace TopicCards\Interfaces;


interface TopicMapDbAdapterInterface
{
    /**
     * TopicMapDbAdapterInterface constructor.
     *
     * @param TopicMapInterface $topicmap
     */
    public function __construct(TopicMapInterface $topicmap);


    /**
     * @param array $filters
     * @return array|int
     */
    public function selectTopics(array $filters);


    public function selectTopicBySubject($uri);


    public function selectTopicSubjectIdentifier($topic_id);


    public function selectTopicSubjectLocator($topic_id);


    public function selectAssociations(array $filters);


    public function selectTopicTypes(array $filters);


    public function selectNameTypes(array $filters);


    public function selectNameScopes(array $filters);


    public function selectOccurrenceTypes(array $filters);


    public function selectOccurrenceDatatypes(array $filters);


    public function selectOccurrenceScopes(array $filters);


    public function selectAssociationTypes(array $filters);


    public function selectAssociationScopes(array $filters);


    public function selectRoleTypes(array $filters);


    public function selectRolePlayers(array $filters);
}
