<?php

namespace TopicCards\Interfaces;


interface TopicInterface extends PersistentInterface
{
    const REIFIES_NONE = '';
    const REIFIES_NAME = 'name';
    const REIFIES_OCCURRENCE = 'occurrence';
    const REIFIES_ASSOCIATION = 'association';
    const REIFIES_ROLE = 'role';

    const EVENT_SAVING = 'topic_saving';
    const EVENT_DELETING = 'topic_deleting';
    const EVENT_INDEXING = 'topic_indexing';

    const ERR_SUBJECT_IDENTIFIER_EXISTS = -11;


    /**
     * @return TopicDbAdapterInterface
     */
    public function getDbAdapter();


    /**
     * @return PersistentSearchAdapterInterface
     */
    public function getSearchAdapter();


    /**
     * @param string $uri
     * @return bool
     */
    public function loadBySubject($uri);


    /**
     * @return string[]
     */
    public function getSubjectIdentifiers();


    /**
     * @param string[] $strings
     * @return self
     */
    public function setSubjectIdentifiers(array $strings);


    /**
     * @return string[]
     */
    public function getSubjectLocators();


    /**
     * @param string[] $strings
     * @return self
     */
    public function setSubjectLocators(array $strings);


    /**
     * @return string[]
     */
    public function getTypeIds();


    /**
     * @param string[] $topicIds
     * @return self
     */
    public function setTypeIds(array $topicIds);


    /**
     * @return string[]
     */
    public function getTypes();


    /**
     * @param string[] $topicSubjects
     * @return self
     */
    public function setTypes(array $topicSubjects);


    /**
     * @param string $topicId
     * @return bool
     */
    public function hasTypeId($topicId);


    /**
     * @param string $topicSubject
     * @return bool
     */
    public function hasType($topicSubject);


    /**
     * @return NameInterface
     */
    public function newName();


    /**
     * @param array $filters
     * @return NameInterface[]
     */
    public function getNames(array $filters = []);


    /**
     * @param array $filters
     * @return NameInterface
     */
    public function getFirstName(array $filters = []);


    /**
     * @param NameInterface[] $names
     * @return self
     */
    public function setNames(array $names);


    /**
     * @return string
     */
    public function getLabel();


    /**
     * @return OccurrenceInterface
     */
    public function newOccurrence();


    /**
     * @param array $filters
     * @return OccurrenceInterface[]
     */
    public function getOccurrences(array $filters = []);


    /**
     * @param array $filters
     * @return OccurrenceInterface
     */
    public function getFirstOccurrence(array $filters = []);


    /**
     * @param OccurrenceInterface[] $occurrences
     * @return self
     */
    public function setOccurrences(array $occurrences);


    /**
     * @return string
     */
    public function getReifiesWhat();


    /**
     * @param string $reifiesWhat
     * @return self
     */
    public function setReifiesWhat($reifiesWhat);


    /**
     * @return string
     */
    public function getReifiesId();


    /**
     * @param string $reifiesId
     * @return self
     */
    public function setReifiesId($reifiesId);


    /**
     * @param string $reifiesWhat
     * @param string $reifiesId
     * @return bool
     */
    public function isReifier(&$reifiesWhat, &$reifiesId);


    /**
     * @return mixed
     */
    public function getReifiedObject();
}
