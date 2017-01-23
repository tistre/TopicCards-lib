<?php

namespace TopicCards\Interfaces;


interface TopicMapDbAdapterInterface
{
    /**
     * TopicMapDbAdapterInterface constructor.
     *
     * @param TopicMapInterface $topicMap
     */
    public function __construct(TopicMapInterface $topicMap);


    /**
     * @param array $filters
     * @return array|int
     */
    public function selectTopics(array $filters);


    public function selectTopicBySubject($uri);


    public function selectTopicSubjectIdentifier($topicId);


    public function selectTopicSubjectLocator($topicId);


    public function selectAssociations(array $filters);


    public function selectTopicTypes(array $filters);


    public function selectNameTypes(array $filters);


    public function selectNameScopes(array $filters);


    public function selectOccurrenceTypes(array $filters);


    public function selectOccurrenceDataTypes(array $filters);


    public function selectOccurrenceScopes(array $filters);


    public function selectAssociationTypes(array $filters);


    public function selectAssociationScopes(array $filters);


    public function selectRoleTypes(array $filters);


    public function selectRolePlayers(array $filters);
}
