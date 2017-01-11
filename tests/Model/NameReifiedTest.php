<?php

use PHPUnit\Framework\TestCase;
use TopicCards\Interfaces\TopicInterface;
use TopicCards\Interfaces\TopicMapInterface;


class NameReifiedTest extends TestCase
{
    /** @var TopicMapInterface */
    protected static $topicmap;


    public static function setUpBeforeClass()
    {
        global $topicmap;

        self::$topicmap = $topicmap;
    }


    public function testNewReifierTopic()
    {
        $topic = self::$topicmap->newTopic();

        $name = $topic->newName();
        $name->setType(TopicMapInterface::SUBJECT_DEFAULT_NAME_TYPE);
        $name->setValue('hello world');

        $name = $topic->getFirstName(['type' => TopicMapInterface::SUBJECT_DEFAULT_NAME_TYPE]);

        $reifier_topic = $name->newReifierTopic();
        $ok = $reifier_topic->save();
        $this->assertGreaterThanOrEqual(0, $ok, 'Reifier save failed');
        
        $name_name = $reifier_topic->newName();
        $name_name->setType(TopicMapInterface::SUBJECT_DEFAULT_NAME_TYPE);
        $name_name->setValue('weird, but this name has a name…');

        $ok = $topic->save();
        $this->assertGreaterThanOrEqual(0, $ok, 'Topic save failed');

        $ok = $topic->load($topic->getId());
        $this->assertGreaterThanOrEqual(0, $ok, 'Topic load after save failed');

        $ok = $reifier_topic->load($reifier_topic->getId());
        $this->assertGreaterThanOrEqual(0, $ok, 'Reifier load after save failed');

        $name = $topic->getFirstName(['type' => TopicMapInterface::SUBJECT_DEFAULT_NAME_TYPE]);

        $this->assertEquals($name->getId(), $reifier_topic->getReifiesId(), 'Reifier topic ID is not the name ID');
        $this->assertEquals(TopicInterface::REIFIES_NAME, $reifier_topic->getReifiesWhat());

        $this->assertEquals($reifier_topic->getId(), $name->getReifierId(),
            'Name reifier ID is not the reifier topic ID');

        $reified_object = $reifier_topic->getReifiedObject();
        $this->assertEquals($name->getId(), $reified_object['name']->getId(),
            'Reified name object ID is not the name ID');
        $this->assertEquals($topic->getId(), $reified_object['topic']->getId(),
            'Reified topic object ID is not the topic ID');

        $reifier_topic->delete();
        $topic->delete();
    }
}
