<?php

namespace TopicCards\Interfaces;

use Psr\Log\LoggerInterface;


interface TopicMapInterface
{
    const SUBJECT_ASSOCIATION_ROLE_TYPE = 'http://psi.topicmaps.org/iso13250/glossary/association-role-type';
    const SUBJECT_ASSOCIATION_TYPE = 'http://psi.topicmaps.org/iso13250/glossary/association-type';
    const SUBJECT_OCCURRENCE_TYPE = 'http://psi.topicmaps.org/iso13250/glossary/occurrence-type';
    const SUBJECT_SCOPE = 'http://psi.topicmaps.org/iso13250/glossary/scope';
    const SUBJECT_TOPIC_NAME_TYPE = 'http://psi.topicmaps.org/iso13250/glossary/topic-name-type';
    const SUBJECT_TOPIC_TYPE = 'http://psi.topicmaps.org/iso13250/glossary/topic-type';
    const SUBJECT_DATATYPE = 'http://www.w3.org/2000/01/rdf-schema#Datatype';
    const SUBJECT_DEFAULT_NAME_TYPE = 'http://schema.org/name';


    /**
     * @param string $id
     * @return self
     */
    public function setId($id);


    /**
     * @return string
     */
    public function getId();


    /**
     * @param LoggerInterface $logger
     * @return self
     */
    public function setLogger(LoggerInterface $logger);


    /**
     * @return LoggerInterface
     */
    public function getLogger();


    /**
     * @param SearchInterface $search
     * @return self
     */
    public function setSearch(SearchInterface $search);


    /**
     * @return SearchInterface
     */
    public function getSearch();


    /**
     * @param DbInterface $db
     * @return self
     */
    public function setDb(DbInterface $db);


    /**
     * @return DbInterface
     */
    public function getDb();


    // TODO Search interface

    public function on($event, callable $callback);


    public function trigger($event, array $params, array &$result);


    /**
     * @param string $url
     * @return self
     */
    public function setUrl($url);


    public function getUrl();


    public function getReifierId();


    /**
     * @return string
     */
    public function createId();


    /**
     * @return TopicInterface
     */
    public function newTopic();


    /**
     * @return AssociationInterface
     */
    public function newAssociation();


    public function newFileTopic($filename);


    public function clearCache();


    public function getTopicIds(array $filters);


    /**
     * @param string $uri
     * @param bool $createTopic
     * @return string
     */
    public function getTopicIdBySubject($uri, $createTopic = false);


    /**
     * @param string $identifier
     * @return string
     */
    public function getTopicIdByIdentifier($identifier);


    public function getTopicSubject($topicId);


    public function getTopicSubjectIdentifier($topicId);


    public function getTopicSubjectLocator($topicId);


    public function getTopicLabel($topicId);


    public function getAssociationIds(array $filters);


    /**
     * @param string $type
     * @param string $id
     * @return string
     */
    public function generateIdentifier($type, $id);
    

    /**
     * @param string $identifier
     * @return bool
     */
    public function isAssociationIdentifier($identifier);


    /**
     * @param string $identifier
     * @return string
     */
    public function getAssociationIdByIdentifier($identifier);


    public function getTopicTypeIds(array $filters);


    public function getNameTypeIds(array $filters);


    public function getNameScopeIds(array $filters);


    public function getOccurrenceTypeIds(array $filters);


    public function getOccurrenceDataTypeIds(array $filters);


    public function getOccurrenceScopeIds(array $filters);


    public function getAssociationTypeIds(array $filters);


    public function getAssociationScopeIds(array $filters);


    public function getRoleTypeIds(array $filters);


    public function getRolePlayerIds(array $filters);


    public function getPreferredLabelScopes();
}
