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
     * @return int
     */
    public function loadBySubject($uri);

    /**
     * @return string[]
     */
    public function getSubjectIdentifiers();

    public function setSubjectIdentifiers(array $strings);

    /**
     * @return string[]
     */
    public function getSubjectLocators();

    public function setSubjectLocators(array $strings);


    /**
     * @return string[]
     */
    public function getTypeIds();
    
    public function setTypeIds(array $topic_ids);


    /**
     * @return string[]
     */
    public function getTypes();
    
    public function setTypes(array $topic_subjects);
    public function hasTypeId($topic_id);
    public function hasType($topic_subject);
    
    /**
     * @return NameInterface
     */
    public function newName();
    
    /**
     * @param array $filters
     * @return NameInterface[]
     */
    public function getNames(array $filters = [ ]);


    /**
     * @param array $filters
     * @return NameInterface
     */
    public function getFirstName(array $filters = [ ]);
    
    public function setNames(array $names);
    
    public function getLabel();


    /**
     * @return OccurrenceInterface
     */
    public function newOccurrence();


    /**
     * @param array $filters
     * @return OccurrenceInterface[]
     */
    public function getOccurrences(array $filters = [ ]);


    /**
     * @param array $filters
     * @return OccurrenceInterface
     */
    public function getFirstOccurrence(array $filters = [ ]);
    
    public function setOccurrences(array $occurrences);

    
    public function getReifiesWhat();
    public function setReifiesWhat($reifies_what);
    public function getReifiesId();
    public function setReifiesId($reifies_id);
    public function isReifier(&$reifies_what, &$reifies_id);
    public function getReifiedObject();
}
