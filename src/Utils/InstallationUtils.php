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

        $this->importXtmFile(TOPICBANK_BASE_DIR . '/install/schema_00_datatypes.xtm');
        $this->importXtmFile(TOPICBANK_BASE_DIR . '/install/schema_01_schema_org.xtm');
        $this->importXtmFile(TOPICBANK_BASE_DIR . '/install/schema_02_files.xtm');
    }


    protected static function initDbTopics(iTopicMap $topicmap)
    {
        $topic_id_name = 'a8ddd773-7ad2-4b44-908c-e0dc7d9d9802';
        $topic_id_concept = '722ac838-4534-4a46-82d1-a60365e37985';

        // schema.org/name

        $topic = $topicmap->newTopic();

        $topic->setId($topic_id_name);
        $topic->setTypeIds([ $topic_id_concept ]);
        $topic->setSubjectIdentifiers([ 'http://schema.org/name', 'https://schema.org/name' ]);

        $name = $topic->newName();
        $name->setTypeId($topic_id_name);
        $name->setValue('Name');

        $ok = $topic->save();

        if ($ok < 0)
        {
            return $ok;
        }
        
        // www.w3.org/2004/02/skos/core#Concept

        $topic = $topicmap->newTopic();

        $topic->setId($topic_id_concept);
        $topic->setSubjectIdentifiers([ 'http://www.w3.org/2004/02/skos/core#Concept' ]);

        $name = $topic->newName();
        $name->setTypeId($topic_id_name);
        $name->setValue('Concept');

        $ok = $topic->save();
        
        return $ok;
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


    public function initSearch()
    {
        $index = $this->topicmap->getSearchIndex();

        $this->services->search->recreateIndex
        (
            $this->topicmap,
            $index,
            $this->services->search->getIndexParams($this->topicmap, $index)
        );

        $this->services->search->reindexAllTopics($this->topicmap);
        $this->services->search->reindexAllAssociations($this->topicmap);

        return 1;
    }
}
