<?php

namespace TopicCards\Db;

use GraphAware\Neo4j\Client\Exception\Neo4jException;
use TopicCards\Interfaces\TopicMapInterface;
use TopicCards\Interfaces\TopicMapDbAdapterInterface;


class TopicMapDbAdapter implements TopicMapDbAdapterInterface
{
    /** @var TopicMapInterface */
    protected $topicmap;
    

    public function __construct(TopicMapInterface $topicmap)
    {
        $this->topicmap = $topicmap;
    }


    public function selectTopics(array $filters)
    {
        $logger = $this->topicmap->getLogger();
        $db = $this->topicmap->getDb();

        if (! isset($filters['limit'])) {
            $filters['limit'] = 500;
        }

        if (isset($filters['type'])) {
            $filters['type_id'] = $this->topicmap->getTopicIdBySubject($filters['type']);
        }

        $db_conn = $db->getConnection();

        if ($db_conn === null) {
            return -1;
        }

        $classes = ['Topic'];

        if (! empty($filters['type_id'])) {
            $classes[] = $filters['type_id'];
        }

        $query = sprintf
        (
            'MATCH (t%s)',
            DbUtils::labelsString($classes)
        );

        $bind = [];

        if (! empty($filters['name_like'])) {
            $query .= '-[:hasName]->(n:Name) WHERE lower(n.value) CONTAINS lower({name_like})';
            $bind['name_like'] = $filters['name_like'];
        }

        $query .= ' RETURN DISTINCT t.id';

        if ($filters['limit'] > 0) {
            $query .= ' LIMIT ' . $filters['limit'];
        }

        $logger->info($query, $bind);

        try {
            $qresult = $db_conn->run($query, $bind);
        } catch (Neo4jException $exception) {
            $logger->error($exception->getMessage());

            // TODO: Error handling
            return -1;
        }

        $result = [];

        foreach ($qresult->records() as $record) {
            $result[] = $record->get('t.id');
        }

        return $result;
    }


    public function selectTopicBySubject($uri)
    {
        $logger = $this->topicmap->getLogger();
        $db = $this->topicmap->getDb();

        if (strlen($uri) === 0) {
            return false;
        }

        $db_conn = $db->getConnection();

        if ($db_conn === null) {
            return -1;
        }

        $query = 'MATCH (n:Topic) WHERE {uri} in n.subject_identifiers RETURN n.id';
        $bind = ['uri' => $uri];

        $logger->info($query, $bind);

        try {
            $qresult = $db_conn->run($query, $bind);
        } catch (Neo4jException $exception) {
            $logger->error($exception->getMessage());

            // TODO: Error handling
            return -1;
        }

        foreach ($qresult->records() as $record) {
            return $record->get('n.id');
        }

        return false;
    }


    public function selectTopicSubjectIdentifier($topic_id)
    {
        return $this->selectTopicSubject($topic_id, 'subject_identifiers');
    }


    public function selectTopicSubjectLocator($topic_id)
    {
        return $this->selectTopicSubject($topic_id, 'subject_locators');
    }


    protected function selectTopicSubject($topic_id, $what)
    {
        $logger = $this->topicmap->getLogger();
        $db = $this->topicmap->getDb();

        if (strlen($topic_id) === 0) {
            return false;
        }

        $db_conn = $db->getConnection();

        if ($db_conn === null) {
            return false;
        }

        $query = 'MATCH (topic { id: {id} }) RETURN topic.' . $what;
        $bind = ['id' => $topic_id];

        $logger->info($query, $bind);

        try {
            $qresult = $db_conn->run($query, $bind);
        } catch (Neo4jException $exception) {
            $logger->error($exception->getMessage());

            return false;
        }

        if ($qresult->size() === 0) {
            return false;
        }

        $record = $qresult->firstRecord();

        $values = $record->get('topic.' . $what);

        if (empty($values)) {
            return false;
        }

        return $values[0];
    }


    public function selectAssociations(array $filters)
    {
        $logger = $this->topicmap->getLogger();
        $db = $this->topicmap->getDb();

        if (isset($filters['type'])) {
            $filters['type_id'] = $this->topicmap->getTopicIdBySubject($filters['type']);
        }

        if (isset($filters['role_player'])) {
            $filters['role_player_id'] = $this->topicmap->getTopicIdBySubject($filters['role_player']);
        }

        if (isset($filters['role_type'])) {
            $filters['role_type_id'] = $this->topicmap->getTopicIdBySubject($filters['role_type']);
        }

        if (! isset($filters['limit'])) {
            $filters['limit'] = 500;
        }

        $db_conn = $db->getConnection();

        if ($db_conn === null) {
            return -1;
        }

        $classes = ['Association'];

        if (! empty($filters['type_id'])) {
            $classes[] = $filters['type_id'];
        }

        $query = sprintf
        (
            'MATCH (a%s)',
            DbUtils::labelsString($classes)
        );

        $bind = [];

        if ((! empty($filters['role_player_id'])) && (! empty($filters['role_type_id']))) {
            $query .= sprintf
            (
                '-[%s]-(t:Topic { id: {player_id} })',
                DbUtils::labelsString([$filters['role_type_id']])
            );

            $bind['player_id'] = $filters['role_player_id'];
        } elseif (! empty($filters['role_player_id'])) {
            $query .= '--(t:Topic { id: {player_id} })';
            $bind['player_id'] = $filters['role_player_id'];
        } elseif (! empty($filters['role_type_id'])) {
            $query .= sprintf
            (
                '-[%s]-(t:Topic)',
                DbUtils::labelsString([$filters['role_type_id']])
            );
        }

        $query .= ' RETURN DISTINCT a.id';

        if ($filters['limit'] > 0) {
            $query .= ' LIMIT ' . $filters['limit'];
        }

        $logger->info($query, $bind);

        try {
            $qresult = $db_conn->run($query, $bind);
        } catch (Neo4jException $exception) {
            $logger->error($exception->getMessage());

            // TODO: Error handling
            return -1;
        }

        $result = [];

        foreach ($qresult->records() as $record) {
            $result[] = $record->get('a.id');
        }

        return $result;
    }


    public function selectTopicTypes(array $filters)
    {
        return $this->selectWhat(TopicMapInterface::SUBJECT_TOPIC_TYPE, $filters);
    }


    public function selectNameTypes(array $filters)
    {
        return $this->selectWhat(TopicMapInterface::SUBJECT_TOPIC_NAME_TYPE, $filters);
    }


    public function selectNameScopes(array $filters)
    {
        // XXX selects all scopes, not just name scopes
        return $this->selectWhat(TopicMapInterface::SUBJECT_SCOPE, $filters);
    }


    public function selectOccurrenceTypes(array $filters)
    {
        return $this->selectWhat(TopicMapInterface::SUBJECT_OCCURRENCE_TYPE, $filters);
    }


    public function selectOccurrenceDatatypes(array $filters)
    {
        return $this->selectWhat(TopicMapInterface::SUBJECT_DATATYPE, $filters);
    }


    public function selectOccurrenceScopes(array $filters)
    {
        // XXX selects all scopes, not just occurrence scopes
        return $this->selectWhat(TopicMapInterface::SUBJECT_SCOPE, $filters);
    }


    public function selectAssociationTypes(array $filters)
    {
        return $this->selectWhat(TopicMapInterface::SUBJECT_ASSOCIATION_TYPE, $filters);
    }


    public function selectAssociationScopes(array $filters)
    {
        // XXX selects all scopes, not just association scopes
        return $this->selectWhat(TopicMapInterface::SUBJECT_SCOPE, $filters);
    }


    public function selectRoleTypes(array $filters)
    {
        return $this->selectWhat(TopicMapInterface::SUBJECT_ASSOCIATION_ROLE_TYPE, $filters);
    }


    public function selectRolePlayers(array $filters)
    {
        // TODO: Currently not implemented
        return [];
    }


    protected function selectWhat($what, array $filters)
    {
        $logger = $this->topicmap->getLogger();
        $db = $this->topicmap->getDb();

        $what_id = $this->topicmap->getTopicIdBySubject($what);

        if (strlen($what_id) === 0) {
            return -1;
        }

        // TODO: Implement both "all" and "recent"; currently it's only "all"

        if (! isset($filters['get_mode'])) {
            $filters['get_mode'] = 'all';
        }

        if (! isset($filters['limit'])) {
            $filters['limit'] = 500;
        }

        $db_conn = $db->getConnection();

        if ($db_conn === null) {
            return -1;
        }

        $query = sprintf
        (
            'MATCH (t%s) RETURN t.id',
            DbUtils::labelsString(['Topic', $what_id])
        );

        $logger->info($query);

        try {
            $qresult = $db_conn->run($query);
        } catch (Neo4jException $exception) {
            $logger->error($exception->getMessage());

            // TODO: Error handling
            return -1;
        }

        $result = [];

        foreach ($qresult->records() as $record) {
            $result[] = $record->get('t.id');
        }

        return $result;
    }
}
