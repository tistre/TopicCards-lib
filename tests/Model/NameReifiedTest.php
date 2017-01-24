<?php

use PHPUnit\Framework\TestCase;
use TopicCards\Interfaces\TopicInterface;
use TopicCards\Interfaces\TopicMapInterface;


class NameReifiedTest extends TestCase
{
    /** @var TopicMapInterface */
    protected static $topicMap;


    public static function setUpBeforeClass()
    {
        global $topicMap;

        self::$topicMap = $topicMap;
    }


    public function testNewReifierTopic()
    {
        $topic = self::$topicMap->newTopic();

        $name = $topic->newName();
        $name->setType(TopicMapInterface::SUBJECT_DEFAULT_NAME_TYPE);
        $name->setValue('hello world');

        $name = $topic->getFirstName(['type' => TopicMapInterface::SUBJECT_DEFAULT_NAME_TYPE]);

        $reifierTopic = $name->newReifierTopic();
        $ok = $reifierTopic->save();
        $this->assertGreaterThanOrEqual(0, $ok, 'Reifier save failed');
        
        $nameName = $reifierTopic->newName();
        $nameName->setType(TopicMapInterface::SUBJECT_DEFAULT_NAME_TYPE);
        $nameName->setValue('weird, but this name has a name…');

        $ok = $topic->save();
        $this->assertGreaterThanOrEqual(0, $ok, 'Topic save failed');

        $ok = $topic->load($topic->getId());
        $this->assertGreaterThanOrEqual(0, $ok, 'Topic load after save failed');

        $ok = $reifierTopic->load($reifierTopic->getId());
        $this->assertGreaterThanOrEqual(0, $ok, 'Reifier load after save failed');

        $name = $topic->getFirstName(['type' => TopicMapInterface::SUBJECT_DEFAULT_NAME_TYPE]);

        $this->assertEquals($name->getId(), $reifierTopic->getReifiesId(), 'Reifier topic ID is not the name ID');
        $this->assertEquals(TopicInterface::REIFIES_NAME, $reifierTopic->getReifiesWhat());

        $this->assertEquals($reifierTopic->getId(), $name->getReifierId(),
            'Name reifier ID is not the reifier topic ID');

        $reifiedObject = $reifierTopic->getReifiedObject();
        
        $this->assertEquals($name->getId(), $reifiedObject['name']->getId(),
            'Reified name object ID is not the name ID');
        $this->assertEquals($topic->getId(), $reifiedObject['topic']->getId(),
            'Reified topic object ID is not the topic ID');

        $reifierTopic->delete();
        $topic->delete();
    }
}
