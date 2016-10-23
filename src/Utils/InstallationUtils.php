<?php

namespace TopicCards\Utils;


use GraphAware\Neo4j\Client\Exception\Neo4jException;
use TopicCards\Interfaces\iTopic;
use TopicCards\Interfaces\iTopicMap;


class InstallationUtils
{
    public static function importXtmFile(iTopicMap $topicmap, $filename)
    {
        $logger = $topicmap->getLogger();
        $db = $topicmap->getDb();

        $db_conn = $db->getConnection();

        if ($db_conn === NULL)
        {
            $logger->emergency('Database connection failed.');
            return -1;
        }

        $ok = 0;
        $logger->info("Starting to import <$filename>...");
        
        $objects = new XtmReader($filename, $topicmap);

        foreach ($objects as $object)
        {
            if (! is_object($object))
            {
                continue;
            }

            $ok = $object->save();

            $subject = '';

            if ($object instanceof iTopic)
            {
                foreach ($object->getSubjectIdentifiers() as $subject)
                {
                    break;
                }

                if ($subject === '')
                {
                    foreach ($object->getSubjectLocators() as $subject)
                    {
                        break;
                    }
                }

                if ($subject !== '')
                {
                    $subject = sprintf('[%s] ', $subject);
                }
            }

            $logger->info(sprintf
            (
                "%s: Created %s %s<%s> (%s)",
                $filename,
                ($object instanceof iTopic ? 'topic' : 'association'),
                $subject,
                $object->getId(),
                $ok
            ));
        }

        return $ok;
    }


    public static function deleteDb(iTopicMap $topicmap)
    {
        // MATCH (n) DETACH DELETE n
    }


    public static function initDb(iTopicMap $topicmap)
    {
        self::initDbConstraints($topicmap);
        self::initDbTopics($topicmap);

        // $this->importXtmFile(TOPICBANK_BASE_DIR . '/install/schema_00_datatypes.xtm');
    }


    protected static function initDbTopics(iTopicMap $topicmap)
    {
        $subjects =
            [
                iTopicMap::SUBJECT_ASSOCIATION_ROLE_TYPE,
                iTopicMap::SUBJECT_ASSOCIATION_TYPE,
                iTopicMap::SUBJECT_DATATYPE,
                iTopicMap::SUBJECT_OCCURRENCE_TYPE,
                iTopicMap::SUBJECT_SCOPE,
                iTopicMap::SUBJECT_TOPIC_NAME_TYPE,
                iTopicMap::SUBJECT_TOPIC_TYPE,
                iTopicMap::SUBJECT_DEFAULT_NAME_TYPE
            ];
        
        foreach ($subjects as $subject)
        {
            $topic = $topicmap->newTopic();

            $topic->setSubjectIdentifiers([ $subject ]);

            $ok = $topic->save();

            if (($ok >= 0) && ($subject === iTopicMap::SUBJECT_DEFAULT_NAME_TYPE))
            {
                $topic_id = $topic->getId();
                
                $name = $topic->newName();
                $name->setTypeId($topic_id);
                $name->setValue('Name');
                
                $topic->save();
            }
        }

        return 1;
    }


    protected static function initDbConstraints(iTopicMap $topicmap)
    {
        $logger = $topicmap->getLogger();
        $db = $topicmap->getDb();

        $db_conn = $db->getConnection();

        if ($db_conn === NULL)
        {
            $logger->emergency('Database connection failed.');
            return -1;
        }

        $queries =
            [
                'CREATE CONSTRAINT ON (t:Topic) ASSERT t.id IS UNIQUE',
                'CREATE CONSTRAINT ON (a:Association) ASSERT a.id IS UNIQUE',
            ];

        foreach ($queries as $query)
        {
            $logger->info($query);

            try
            {
                $db_conn->run($query);
            }
            catch (Neo4jException $exception)
            {
                $logger->error($exception->getMessage());
                // TODO: Error handling
                return -1;
            }
        }
    }


    public function initSearch(iTopicMap $topicmap)
    {
        $search = $topicmap->getSearch();
        $index = $search->getIndexName();

        $search->recreateIndex
        (
            $topicmap,
            $index,
            $search->getIndexParams($topicmap, $index)
        );

        $search->reindexAllTopics($topicmap);
        $search->reindexAllAssociations($topicmap);

        return 1;
    }
}
