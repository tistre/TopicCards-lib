<?php

use PHPUnit\Framework\TestCase;
use TopicCards\Interfaces\TopicMapInterface;
use TopicCards\Utils\DataTypeUtils;


class OccurrenceTest extends TestCase
{
    /** @var TopicMapInterface */
    protected static $topicMap;


    public static function setUpBeforeClass()
    {
        global $topicMap;

        self::$topicMap = $topicMap;
    }


    public function testNewTypeAndDatatype()
    {
        $occurrenceType = 'http://example.com/schema/occurrenceType' . __FILE__;
        $dataType = 'http://example.com/schema/datatype' . __FILE__;

        $topic = self::$topicMap->newTopic();

        $occurrence = $topic->newOccurrence();

        $occurrence->setType($occurrenceType);
        $occurrence->setDataType($dataType);
        $occurrence->setValue('hello world');

        $ok = $topic->save();

        $this->assertGreaterThanOrEqual(0, $ok, 'Topic save failed');

        $ok = $topic->load($topic->getId());

        $this->assertGreaterThanOrEqual(0, $ok, 'Topic load after save failed');

        $occurrence = $topic->getFirstOccurrence(['type' => $occurrenceType]);

        $expected =
            [
                'value' => 'hello world',
                'reifier' => false,
                'scope' => []
            ];

        $occurrenceData = $occurrence->getAll();

        $this->assertArraySubset($expected, $occurrenceData);

        $occurrenceTypeTopic = self::$topicMap->newTopic();
        $ok = $occurrenceTypeTopic->load($occurrence->getTypeId());
        $this->assertGreaterThanOrEqual(0, $ok, 'Occurrence type topic load failed');

        $this->assertContains
        (
            TopicMapInterface::SUBJECT_OCCURRENCE_TYPE,
            $occurrenceTypeTopic->getTypes(),
            'New occurrence type topic has not been marked as occurrence type.'
        );

        $dataTypeTopic = self::$topicMap->newTopic();
        $ok = $dataTypeTopic->load($occurrence->getDataTypeId());
        $this->assertGreaterThanOrEqual(0, $ok, 'Datatype topic load failed');

        $this->assertContains
        (
            TopicMapInterface::SUBJECT_DATATYPE,
            $dataTypeTopic->getTypes(),
            'New datatype has not been marked as datatype.'
        );

        $topic->delete();
        $dataTypeTopic->delete();
        $occurrenceTypeTopic->delete();
    }
    
    
    public function testLanguage()
    {
        $occurrenceType = 'http://schema.org/text';
        
        $topic = self::$topicMap->newTopic();

        $occurrence = $topic->newOccurrence();

        $occurrence->setType($occurrenceType);
        $occurrence->setValue('hello world');
        $occurrence->setLanguage('en');

        $ok = $topic->save();

        $this->assertGreaterThanOrEqual(0, $ok, 'Topic save failed');

        $ok = $topic->load($topic->getId());

        $this->assertGreaterThanOrEqual(0, $ok, 'Topic load after save failed');

        $occurrence = $topic->getFirstOccurrence(['type' => $occurrenceType]);

        $expected =
            [
                'value' => 'hello world',
                'datatype' => self::$topicMap->getTopicIdBySubject(DataTypeUtils::DATATYPE_STRING),
                'language' => 'en',
                'reifier' => false,
                'scope' => []
            ];

        $occurrenceData = $occurrence->getAll();

        $this->assertArraySubset($expected, $occurrenceData);

        $topic->delete();
    }
}
