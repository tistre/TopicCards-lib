<?php

namespace TopicCards\Utils;


use GraphAware\Neo4j\Client\Exception\Neo4jException;
use TopicCards\Interfaces\TopicInterface;
use TopicCards\Interfaces\TopicMapInterface;


class InstallationUtils
{
    public static function importXtmFile(TopicMapInterface $topicmap, $filename)
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

            if ($object instanceof TopicInterface)
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
                ($object instanceof TopicInterface ? 'topic' : 'association'),
                $subject,
                $object->getId(),
                $ok
            ));
        }

        return $ok;
    }


    public static function deleteDb(TopicMapInterface $topicmap)
    {
        // MATCH (n) DETACH DELETE n
    }


    public static function initDb(TopicMapInterface $topicmap)
    {
        self::initDbConstraints($topicmap);
        self::initDbTopics($topicmap);

        // $this->importXtmFile(TOPICBANK_BASE_DIR . '/install/schema_00_datatypes.xtm');
    }


    protected static function initDbTopics(TopicMapInterface $topicmap)
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

        foreach ($subjects as $subject => $name_str)
        {
            $topic = $topicmap->newTopic();

            $topic->setSubjectIdentifiers([ $subject ]);

            if ($subject !== TopicMapInterface::SUBJECT_DEFAULT_NAME_TYPE)
            {
                $name = $topic->newName();
                $name->setType(TopicMapInterface::SUBJECT_DEFAULT_NAME_TYPE);
                $name->setValue($name_str);
            }
            
            $topic->save();
        }

        // TODO: Set name of name to Name :)
        
        return 1;
    }


    protected static function initDbConstraints(TopicMapInterface $topicmap)
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


    public function initSearch(TopicMapInterface $topicmap)
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
