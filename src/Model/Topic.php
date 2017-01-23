<?php

namespace TopicCards\Model;

use TopicCards\Db\TopicDbAdapter;
use TopicCards\Interfaces\NameInterface;
use TopicCards\Interfaces\OccurrenceInterface;
use TopicCards\Interfaces\PersistentSearchAdapterInterface;
use TopicCards\Interfaces\TopicInterface;
use TopicCards\Interfaces\TopicDbAdapterInterface;
use TopicCards\Interfaces\TopicMapInterface;
use TopicCards\Search\TopicSearchAdapter;


class Topic extends Core implements TopicInterface
{
    use Persistent;

    protected $subjectIdentifiers = [];
    protected $subjectLocators = [];
    protected $types = [];

    /** @var NameInterface[] */
    protected $names = [];

    /** @var OccurrenceInterface[] */
    protected $occurrences = [];

    protected $reifiesWhat = '';
    protected $reifiesId = '';

    /** @var TopicDbAdapterInterface */
    protected $dbAdapter;

    /** @var PersistentSearchAdapterInterface */
    protected $searchAdapter;


    /**
     * Topic constructor.
     *
     * @param TopicMapInterface $topicMap
     */
    public function __construct(TopicMapInterface $topicMap)
    {
        parent::__construct($topicMap);

        $this->dbAdapter = new TopicDbAdapter($this);
        $this->searchAdapter = new TopicSearchAdapter($this);
    }


    /**
     * @return TopicDbAdapterInterface
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
        return $this->searchAdapter;
    }


    public function loadBySubject($uri)
    {
        $id = $this->topicMap->getTopicIdBySubject($uri);

        if (strlen($id) === 0) {
            return -1;
        }

        return $this->load($id);
    }


    public function getSubjectIdentifiers()
    {
        return $this->subjectIdentifiers;
    }


    public function setSubjectIdentifiers(array $strings)
    {
        $this->subjectIdentifiers = $strings;

        return 1;
    }


    public function getSubjectLocators()
    {
        return $this->subjectLocators;
    }


    public function setSubjectLocators(array $strings)
    {
        $this->subjectLocators = $strings;

        return 1;
    }


    public function getTypeIds()
    {
        return $this->types;
    }


    public function setTypeIds(array $topicIds)
    {
        $this->types = $topicIds;

        return 1;
    }


    public function getTypes()
    {
        $result = [];

        foreach ($this->getTypeIds() as $topicId) {
            $result[] = $this->topicMap->getTopicSubject($topicId);
        }

        return $result;
    }


    public function setTypes(array $topicSubjects)
    {
        $topicIds = [];
        $result = 1;

        foreach ($topicSubjects as $topicSubject) {
            $topicId = $this->topicMap->getTopicIdBySubject($topicSubject, true);

            if (strlen($topicId) === 0) {
                $result = -1;
            } else {
                $topicIds[] = $topicId;
            }
        }

        $ok = $this->setTypeIds($topicIds);

        if ($ok < 0) {
            $result = $ok;
        }

        return $result;
    }


    public function hasTypeId($topicId)
    {
        return in_array($topicId, $this->types);
    }


    public function hasType($topicSubject)
    {
        return $this->hasTypeId($this->topicMap->getTopicIdBySubject($topicSubject));
    }


    public function newName()
    {
        $name = new Name($this->topicMap);

        $this->names[] = $name;

        return $name;
    }


    /**
     * @param array $filters
     * @return NameInterface[]
     */
    public function getNames(array $filters = [])
    {
        if (count($filters) === 0) {
            return $this->names;
        }

        $result = [];

        if (isset($filters['type'])) {
            $filters['type_id'] = $this->topicMap->getTopicIdBySubject($filters['type']);
        }

        foreach ($this->names as $name) {
            if (isset($filters['id'])) {
                if ($name->getId() !== $filters['id']) {
                    continue;
                }
            }

            if (isset($filters['value'])) {
                if ($name->getValue() !== $filters['value']) {
                    continue;
                }
            }

            if (isset($filters['type_id'])) {
                if ($name->getTypeId() !== $filters['type_id']) {
                    continue;
                }
            }

            if (isset($filters['reifier'])) {
                if ($name->getReifierId() !== $filters['reifier']) {
                    continue;
                }
            }

            $result[] = $name;
        }

        return $result;
    }


    public function getFirstName(array $filters = [])
    {
        $names = $this->getNames($filters);

        if (count($names) > 0) {
            return $names[0];
        }

        $name = $this->newName();

        if (isset($filters['type'])) {
            $name->setType($filters['type']);
        } elseif (isset($filters['type_id'])) {
            $name->setTypeId($filters['type_id']);
        } elseif (isset($filters['id'])) {
            $name->setId($filters['id']);
        }

        return $name;
    }


    public function setNames(array $names)
    {
        $this->names = $names;

        return 1;
    }


    public function getLabel($preferredScopes = false)
    {
        if (! is_array($preferredScopes)) {
            $preferredScopes = $this->topicMap->getPreferredLabelScopes();
        }

        // Preferred scopes in ascending order.
        // Prefer http://schema.org/name ("default"), otherwise use first name

        $byScope = array_fill_keys
        (
            array_keys($preferredScopes),
            ['default' => [], 'other' => []]
        );

        foreach ($this->getNames([]) as $name) {
            $typeKey =
                (
                ($name->getType() === TopicMapInterface::SUBJECT_DEFAULT_NAME_TYPE)
                    ? 'default'
                    : 'other'
                );

            foreach ($preferredScopes as $scopeKey => $scope) {
                if (($scope !== '*') && (! $name->matchesScope($scope))) {
                    continue;
                }

                $value = $name->getValue();

                if (strlen($value) === 0) {
                    continue;
                }

                $byScope[$scopeKey][$typeKey][] = $value;
            }
        }

        foreach ($byScope as $scopeKey => $byType) {
            foreach ($byType as $values) {
                if (isset($values[0])) {
                    return $values[0];
                }
            }
        }

        foreach ($this->getSubjectIdentifiers() as $value) {
            return $value;
        }

        return '';
    }


    public function newOccurrence()
    {
        $occurrence = new Occurrence($this->topicMap);

        $this->occurrences[] = $occurrence;

        return $occurrence;
    }


    /**
     * @param array $filters
     * @return OccurrenceInterface[]
     */
    public function getOccurrences(array $filters = [])
    {
        if (count($filters) === 0) {
            return $this->occurrences;
        }

        if (isset($filters['type'])) {
            $filters['type_id'] = $this->topicMap->getTopicIdBySubject($filters['type']);
        }

        $result = [];

        foreach ($this->occurrences as $occurrence) {
            if (isset($filters['type_id'])) {
                if ($occurrence->getTypeId() !== $filters['type_id']) {
                    continue;
                }
            }

            if (isset($filters['id'])) {
                if ($occurrence->getId() !== $filters['id']) {
                    continue;
                }
            }

            if (isset($filters['value'])) {
                if ($occurrence->getValue() !== $filters['value']) {
                    continue;
                }
            }

            $result[] = $occurrence;
        }

        return $result;
    }


    public function getFirstOccurrence(array $filters = [])
    {
        $occurrences = $this->getOccurrences($filters);

        if (count($occurrences) > 0) {
            return $occurrences[0];
        }

        $occurrence = $this->newOccurrence();

        if (isset($filters['type'])) {
            $occurrence->setType($filters['type']);
        } elseif (isset($filters['type_id'])) {
            $occurrence->setTypeId($filters['type_id']);
        } elseif (isset($filters['id'])) {
            $occurrence->setId($filters['id']);
        }

        return $occurrence;
    }


    public function setOccurrences(array $occurrences)
    {
        $this->occurrences = $occurrences;

        return 1;
    }


    /**
     * @return string
     */
    public function getReifiesWhat()
    {
        return $this->reifiesWhat;
    }


    /**
     * @param string $reifiesWhat
     */
    public function setReifiesWhat($reifiesWhat)
    {
        $this->reifiesWhat = $reifiesWhat;
    }


    /**
     * @return string
     */
    public function getReifiesId()
    {
        return $this->reifiesId;
    }


    /**
     * @param string $reifiesId
     */
    public function setReifiesId($reifiesId)
    {
        $this->reifiesId = $reifiesId;
    }


    public function isReifier(&$reifiesWhat, &$reifiesId)
    {
        $reifiesWhat = $this->getReifiesWhat();
        $reifiesId = $this->getReifiesId();

        return (($reifiesWhat !== TopicInterface::REIFIES_NONE) && (strlen($reifiesId) > 0));
    }


    public function getReifiedObject()
    {
        return $this->dbAdapter->selectReifiedObject();
    }


    public function validate(&$msgHtml)
    {
        $result = 1;
        $msgHtml = '';

        // We want unique subject identifiers, but Neo4j constraints cannot
        // uniquely index multi-valued properties. So let's check for ourselves (yes,
        // there's room for race conditions here.)

        if ($this->getVersion() === 0) {
            foreach ($this->getSubjectIdentifiers() as $subjectIdentifier) {
                if (strlen($this->topicMap->getTopicIdBySubject($subjectIdentifier)) > 0) {
                    $result = TopicInterface::ERR_SUBJECT_IDENTIFIER_EXISTS;
                    $msgHtml .= sprintf('Subject identifier "%s" already exists.', $subjectIdentifier);
                }
            }
        }

        foreach (array_merge($this->getNames([]), $this->getOccurrences([])) as $obj) {
            $ok = $obj->validate($msg);

            if ($ok < 0) {
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
                'types' => $this->getTypeIds(),
                'subject_identifiers' => $this->getSubjectIdentifiers(),
                'subject_locators' => $this->getSubjectLocators(),
                'names' => [],
                'occurrences' => [],
                'reifies_what' => $this->getReifiesWhat(),
                'reifies_id' => $this->getReifiesId()
            ];

        foreach ($this->names as $name) {
            $result['names'][] = $name->getAll();
        }

        foreach ($this->occurrences as $occurrence) {
            $result['occurrences'][] = $occurrence->getAll();
        }

        $result = array_merge($result, $this->getAllId());
        $result = array_merge($result, $this->getAllPersistent());

        ksort($result);

        return $result;
    }


    public function setAll(array $data)
    {
        $data = array_merge(
            [
                'types' => [],
                'subject_identifiers' => [],
                'subject_locators' => [],
                'names' => [],
                'occurrences' => [],
                'reifies_what' => TopicInterface::REIFIES_NONE,
                'reifies_id' => ''
            ], $data);

        $this->setAllId($data);

        $this->setAllPersistent($data);

        $this->setTypeIds($data['types']);

        $this->setSubjectIdentifiers($data['subject_identifiers']);

        $this->setSubjectLocators($data['subject_locators']);

        $this->setNames([]);

        foreach ($data['names'] as $nameData) {
            $name = $this->newName();
            $name->setAll($nameData);
        }

        $this->setOccurrences([]);

        foreach ($data['occurrences'] as $occurrenceData) {
            $occurrence = $this->newOccurrence();
            $occurrence->setAll($occurrenceData);
        }

        $this->setReifiesWhat($data['reifies_what']);
        $this->setReifiesId($data['reifies_id']);

        return 1;
    }


    // XXX to be implemented: if this topic is a reifier, empty
    // the reifier property in the reifying object on delete    
}
