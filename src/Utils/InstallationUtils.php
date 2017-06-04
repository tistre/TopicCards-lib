<?php

namespace TopicCards\Utils;


use GraphAware\Neo4j\Client\Exception\Neo4jException;
use TopicCards\Exception\TopicCardsException;
use TopicCards\Exception\TopicCardsRuntimeException;
use TopicCards\Interfaces\TopicInterface;
use TopicCards\Interfaces\TopicMapInterface;


class InstallationUtils
{
    public static function importXtmFile(TopicMapInterface $topicMap, $fileName)
    {
        $logger = $topicMap->getLogger();
        $db = $topicMap->getDb();

        $dbConn = $db->getConnection();

        if ($dbConn === null) {
            throw new TopicCardsRuntimeException(sprintf
            (
                '%s: Failed to get db connection.',
                __METHOD__
            ));
        }

        $ok = 0;
        $logger->info("Starting to import <$fileName>...");

        $objects = new XtmReader($fileName, $topicMap);

        foreach ($objects as $object) {
            if (! is_object($object)) {
                continue;
            }

            $ok = $object->save();

            $subject = '';

            if ($object instanceof TopicInterface) {
                foreach ($object->getSubjectIdentifiers() as $subject) {
                    break;
                }

                if ($subject === '') {
                    foreach ($object->getSubjectLocators() as $subject) {
                        break;
                    }
                }

                if ($subject !== '') {
                    $subject = sprintf('[%s] ', $subject);
                }
            }

            $logger->info(sprintf
            (
                "%s: Created %s %s<%s> (%s)",
                $fileName,
                ($object instanceof TopicInterface ? 'topic' : 'association'),
                $subject,
                $object->getId(),
                $ok
            ));
        }

        return $ok;
    }


    public static function deleteDb(TopicMapInterface $topicMap)
    {
        // TODO: MATCH (n) DETACH DELETE n
    }


    public static function initDb(TopicMapInterface $topicMap)
    {
        self::initDbConstraints($topicMap);
        self::initDbTopics($topicMap);

        // $this->importXtmFile(TOPICBANK_BASE_DIR . '/install/schema_00_datatypes.xtm');
    }


    protected static function initDbTopics(TopicMapInterface $topicMap)
    {
        $subjects =
            [
                // Name must come first!
                TopicMapInterface::SUBJECT_DEFAULT_NAME_TYPE => 'Name',
                TopicMapInterface::SUBJECT_ASSOCIATION_ROLE_TYPE => 'Association role type',
                TopicMapInterface::SUBJECT_ASSOCIATION_TYPE => 'Association type',
                TopicMapInterface::SUBJECT_DATATYPE => 'Datatype',
                TopicMapInterface::SUBJECT_OCCURRENCE_TYPE => 'Property type',
                TopicMapInterface::SUBJECT_SCOPE => 'Scope',
                TopicMapInterface::SUBJECT_TOPIC_NAME_TYPE => 'Name type',
                TopicMapInterface::SUBJECT_TOPIC_TYPE => 'Topic type'
            ];

        foreach ($subjects as $subject => $nameStr) {
            $topic = $topicMap->newTopic();

            $topic->setSubjectIdentifiers([$subject]);

            if ($subject !== TopicMapInterface::SUBJECT_DEFAULT_NAME_TYPE) {
                $name = $topic->newName();
                $name->setType(TopicMapInterface::SUBJECT_DEFAULT_NAME_TYPE);
                $name->setValue($nameStr);
            }

            try {
                $topic->save();
            } catch (TopicCardsException $exception) {
                if ($exception->getCode() !== TopicInterface::ERR_SUBJECT_IDENTIFIER_EXISTS) {
                    throw $exception;
                }
            }
        }

        // TODO: Set name of name to Name :)

        return 1;
    }


    protected static function initDbConstraints(TopicMapInterface $topicMap)
    {
        $logger = $topicMap->getLogger();
        $db = $topicMap->getDb();

        $dbConn = $db->getConnection();

        if ($dbConn === null) {
            throw new TopicCardsRuntimeException(sprintf
            (
                '%s: Failed to get db connection.',
                __METHOD__
            ));
        }

        $queries =
            [
                'CREATE CONSTRAINT ON (t:Topic) ASSERT t.id IS UNIQUE',
                'CREATE CONSTRAINT ON (a:Association) ASSERT a.id IS UNIQUE',
            ];

        foreach ($queries as $query) {
            $logger->info($query);

            try {
                $dbConn->run($query);
            } catch (Neo4jException $exception) {
                throw new TopicCardsRuntimeException
                (
                    sprintf
                    (
                        '%s: Neo4j run failed.',
                        __METHOD__
                    ),
                    0,
                    $exception
                );
            }
        }
    }


    protected static function initDbIndexes(TopicMapInterface $topicMap)
    {
        $logger = $topicMap->getLogger();
        $db = $topicMap->getDb();

        $dbConn = $db->getConnection();

        if ($dbConn === null) {
            throw new TopicCardsRuntimeException(sprintf
            (
                '%s: Failed to get db connection.',
                __METHOD__
            ));
        }

        $queries =
            [
                'CREATE INDEX ON :Topic(subject_identifiers)'
            ];

        foreach ($queries as $query) {
            $logger->info($query);

            try {
                $dbConn->run($query);
            } catch (Neo4jException $exception) {
                throw new TopicCardsRuntimeException
                (
                    sprintf
                    (
                        '%s: Neo4j run failed.',
                        __METHOD__
                    ),
                    0,
                    $exception
                );
            }
        }
    }


    public function initSearch(TopicMapInterface $topicMap)
    {
        $search = $topicMap->getSearch();
        $index = $search->getIndexName();

        $search->recreateIndex
        (
            $topicMap,
            $index,
            $search->getIndexParams($topicMap, $index)
        );

        $search->reindexAllTopics($topicMap);
        $search->reindexAllAssociations($topicMap);

        return 1;
    }
}
