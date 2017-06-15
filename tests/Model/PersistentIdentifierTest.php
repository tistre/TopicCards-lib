<?php

use PHPUnit\Framework\TestCase;
use TopicCards\Interfaces\TopicMapInterface;


class PersistentIdentifierTest extends TestCase
{
    /** @var TopicMapInterface */
    protected static $topicMap;


    public static function setUpBeforeClass()
    {
        global $topicMap;

        self::$topicMap = $topicMap;
    }
    

    public function testTopicId()
    {
        $topic = self::$topicMap->newTopic();
        $topic->save();

        $topicId = $topic->getId();
        
        $ok = $topic->load($topicId);

        $this->assertEquals(true, $ok, 'Topic load after save failed');

        $identifier = $topic->getIdentifier();
        
        $ok = $topic->load(self::$topicMap->getTopicIdByIdentifier($identifier));

        $this->assertEquals(true, $ok, 'Topic load by identifier failed');
        $this->assertEquals($topicId, $topic->getId(), 'Wrong topic ID after load');

        $topic->delete();
    }


    public function testTopicSubject()
    {
        $topicSubject = 'http://example.com/topic' . __FILE__;
        
        $topic = self::$topicMap->newTopic();
        $topic->setSubjectIdentifiers([$topicSubject]);
        $topic->save();

        $topicId = $topic->getId();

        $ok = $topic->load($topicId);

        $this->assertEquals(true, $ok, 'Topic load after save failed');

        $identifier = $topic->getIdentifier();

        $this->assertEquals($identifier, $topicSubject, 'Identifier does not equal the subject');

        $ok = $topic->load(self::$topicMap->getTopicIdByIdentifier($identifier));

        $this->assertEquals(true, $ok, 'Topic load by identifier failed');
        $this->assertEquals($topicId, $topic->getId(), 'Wrong topic ID after load');

        $topic->delete();
    }
}
