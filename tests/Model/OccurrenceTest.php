<?php

use PHPUnit\Framework\TestCase;
use TopicCards\Interfaces\TopicMapInterface;


class OccurrenceTest extends TestCase
{
    /** @var TopicMapInterface */
    protected static $topicmap;


    public static function setUpBeforeClass()
    {
        global $topicmap;

        self::$topicmap = $topicmap;
    }


    public function testNewTypeAndDatatype()
    {
        $occurrence_type = 'http://example.com/schema/occurrenceType' . __FILE__;
        $datatype = 'http://example.com/schema/datatype' . __FILE__;

        $topic = self::$topicmap->newTopic();

        $occurrence = $topic->newOccurrence();

        $occurrence->setType($occurrence_type);
        $occurrence->setDatatype($datatype);
        $occurrence->setValue('hello world');

        $ok = $topic->save();

        $this->assertGreaterThanOrEqual(0, $ok, 'Topic save failed');

        $ok = $topic->load($topic->getId());

        $this->assertGreaterThanOrEqual(0, $ok, 'Topic load after save failed');

        $occurrence = $topic->getFirstOccurrence(['type' => $occurrence_type]);

        $expected =
            [
                'value' => 'hello world',
                'reifier' => false,
                'scope' => []
            ];

        $occurrence_data = $occurrence->getAll();

        $this->assertArraySubset($expected, $occurrence_data);

        $occurrence_type_topic = self::$topicmap->newTopic();
        $ok = $occurrence_type_topic->load($occurrence->getTypeId());
        $this->assertGreaterThanOrEqual(0, $ok, 'Occurrence type topic load failed');

        $this->assertContains
        (
            TopicMapInterface::SUBJECT_OCCURRENCE_TYPE,
            $occurrence_type_topic->getTypes(),
            'New occurrence type topic has not been marked as occurrence type.'
        );

        $datatype_topic = self::$topicmap->newTopic();
        $ok = $datatype_topic->load($occurrence->getDatatypeId());
        $this->assertGreaterThanOrEqual(0, $ok, 'Datatype topic load failed');

        $this->assertContains
        (
            TopicMapInterface::SUBJECT_DATATYPE,
            $datatype_topic->getTypes(),
            'New datatype has not been marked as datatype.'
        );

        $topic->delete();
        $datatype_topic->delete();
        $occurrence_type_topic->delete();
    }
}
