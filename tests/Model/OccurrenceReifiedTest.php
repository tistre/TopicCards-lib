<?php

use PHPUnit\Framework\TestCase;
use TopicCards\Interfaces\TopicInterface;
use TopicCards\Interfaces\TopicMapInterface;
use TopicCards\Utils\DatatypeUtils;


class OccurrenceReifiedTest extends TestCase
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
        $occurrence_type = 'http://schema.org/description';

        // Create topic, reify occurrence

        $topic = self::$topicmap->newTopic();

        $occurrence = $topic->newOccurrence();
        $occurrence->setType($occurrence_type);
        $occurrence->setDatatype(DatatypeUtils::DATATYPE_STRING);
        $occurrence->setValue('hello world');

        $reifier_topic = $occurrence->newReifierTopic();
        $ok = $reifier_topic->save();
        $this->assertGreaterThanOrEqual(0, $ok, 'Reifier save failed');

        $ok = $topic->save();
        $this->assertGreaterThanOrEqual(0, $ok, 'Topic save failed');

        // Reload

        $ok = $topic->load($topic->getId());
        $this->assertGreaterThanOrEqual(0, $ok, 'Topic load after save failed');

        $ok = $reifier_topic->load($reifier_topic->getId());
        $this->assertGreaterThanOrEqual(0, $ok, 'Reifier load after save failed');

        // Verify 

        $occurrence = $topic->getFirstOccurrence(['type' => $occurrence_type]);

        $this->assertEquals($occurrence->getId(), $reifier_topic->getReifiesId(),
            'Reifier topic ID is not the occurrence ID');
        $this->assertEquals(TopicInterface::REIFIES_OCCURRENCE, $reifier_topic->getReifiesWhat(),
            'Reifier "what" is not "occurrence"');

        $this->assertEquals($reifier_topic->getId(), $occurrence->getReifierId(),
            'Occurrence reifier ID is not the reifier topic ID');

        $reified_object = $reifier_topic->getReifiedObject();
        $this->assertEquals($occurrence->getId(), $reified_object['occurrence']->getId(),
            'Reified occurrence object ID is not the occurrence ID');
        $this->assertEquals($topic->getId(), $reified_object['topic']->getId(),
            'Reified topic object ID is not the topic ID');

        // Remove reification

        $occurrence->setReifierId('');
        $ok = $topic->save();
        $this->assertGreaterThanOrEqual(0, $ok, 'Topic save failed');

        // Reload

        $ok = $topic->load($topic->getId());
        $this->assertGreaterThanOrEqual(0, $ok, 'Topic load after save failed');

        $ok = $reifier_topic->load($reifier_topic->getId());
        $this->assertGreaterThanOrEqual(0, $ok, 'Reifier load after save failed');

        // Verify 

        $occurrence = $topic->getFirstOccurrence(['type' => $occurrence_type]);

        $this->assertEquals('', $reifier_topic->getReifiesId(), 'Reifier topic ID is not empty');
        $this->assertEquals(TopicInterface::REIFIES_NONE, $reifier_topic->getReifiesWhat(),
            'Reifier "what" is not "none"');

        $this->assertEquals('', $occurrence->getReifierId(), 'Occurrence reifier ID is not empty');

        // Cleanup

        $reifier_topic->delete();
        $topic->delete();
    }
}
