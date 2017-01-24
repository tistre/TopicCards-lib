<?php

use PHPUnit\Framework\TestCase;
use TopicCards\Interfaces\TopicInterface;
use TopicCards\Interfaces\TopicMapInterface;
use TopicCards\Utils\DatatypeUtils;


class OccurrenceReifiedTest extends TestCase
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
        $occurrenceType = 'http://schema.org/description';

        // Create topic, reify occurrence

        $topic = self::$topicMap->newTopic();

        $occurrence = $topic->newOccurrence();
        $occurrence->setType($occurrenceType);
        $occurrence->setDataType(DatatypeUtils::DATATYPE_STRING);
        $occurrence->setValue('hello world');

        $reifierTopic = $occurrence->newReifierTopic();
        $ok = $reifierTopic->save();
        $this->assertGreaterThanOrEqual(0, $ok, 'Reifier save failed');

        $ok = $topic->save();
        $this->assertGreaterThanOrEqual(0, $ok, 'Topic save failed');

        // Reload

        $ok = $topic->load($topic->getId());
        $this->assertGreaterThanOrEqual(0, $ok, 'Topic load after save failed');

        $ok = $reifierTopic->load($reifierTopic->getId());
        $this->assertGreaterThanOrEqual(0, $ok, 'Reifier load after save failed');

        // Verify 

        $occurrence = $topic->getFirstOccurrence(['type' => $occurrenceType]);

        $this->assertEquals($occurrence->getId(), $reifierTopic->getReifiesId(),
            'Reifier topic ID is not the occurrence ID');
        $this->assertEquals(TopicInterface::REIFIES_OCCURRENCE, $reifierTopic->getReifiesWhat(),
            'Reifier "what" is not "occurrence"');

        $this->assertEquals($reifierTopic->getId(), $occurrence->getReifierId(),
            'Occurrence reifier ID is not the reifier topic ID');

        $reifiedObject = $reifierTopic->getReifiedObject();
        
        $this->assertEquals($occurrence->getId(), $reifiedObject['occurrence']->getId(),
            'Reified occurrence object ID is not the occurrence ID');
        $this->assertEquals($topic->getId(), $reifiedObject['topic']->getId(),
            'Reified topic object ID is not the topic ID');

        // Remove reification

        $occurrence->setReifierId('');
        $ok = $topic->save();
        $this->assertGreaterThanOrEqual(0, $ok, 'Topic save failed');

        // Reload

        $ok = $topic->load($topic->getId());
        $this->assertGreaterThanOrEqual(0, $ok, 'Topic load after save failed');

        $ok = $reifierTopic->load($reifierTopic->getId());
        $this->assertGreaterThanOrEqual(0, $ok, 'Reifier load after save failed');

        // Verify 

        $occurrence = $topic->getFirstOccurrence(['type' => $occurrenceType]);

        $this->assertEquals('', $reifierTopic->getReifiesId(), 'Reifier topic ID is not empty');
        $this->assertEquals(TopicInterface::REIFIES_NONE, $reifierTopic->getReifiesWhat(),
            'Reifier "what" is not "none"');

        $this->assertEquals('', $occurrence->getReifierId(), 'Occurrence reifier ID is not empty');

        // Cleanup

        $reifierTopic->delete();
        $topic->delete();
    }
}
