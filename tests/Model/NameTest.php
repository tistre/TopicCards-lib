<?php

use PHPUnit\Framework\TestCase;
use TopicCards\Interfaces\TopicMapInterface;
use TopicCards\Utils\DataTypeUtils;


class NameTest extends TestCase
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
        $topic = self::$topicMap->newTopic();

        $name = $topic->newName();
        $name->setType(TopicMapInterface::SUBJECT_DEFAULT_NAME_TYPE);
        $name->setValue('hello world');

        $ok = $topic->save();

        $this->assertGreaterThanOrEqual(0, $ok, 'Topic save failed');

        $ok = $topic->load($topic->getId());

        $this->assertGreaterThanOrEqual(0, $ok, 'Topic load after save failed');

        $name = $topic->getFirstName(['type' => TopicMapInterface::SUBJECT_DEFAULT_NAME_TYPE]);

        $expected =
            [
                'value' => 'hello world',
                'reifier' => false,
                'scope' => false
            ];

        $nameData = $name->getAll();

        $this->assertArraySubset($expected, $nameData);

        $nameTypeTopic = self::$topicMap->newTopic();
        $ok = $nameTypeTopic->load($name->getTypeId());
        $this->assertGreaterThanOrEqual(0, $ok, 'Name type topic load failed');

        $this->assertContains
        (
            TopicMapInterface::SUBJECT_TOPIC_NAME_TYPE,
            $nameTypeTopic->getTypes(),
            'New name type topic has not been marked as name type.'
        );

        $topic->delete();
    }
    
    
    public function testLanguage()
    {
        $topic = self::$topicMap->newTopic();

        $name = $topic->newName();
        $name->setType(TopicMapInterface::SUBJECT_DEFAULT_NAME_TYPE);
        $name->setValue('hello world');
        $name->setLanguage('en');

        $ok = $topic->save();

        $this->assertGreaterThanOrEqual(0, $ok, 'Topic save failed');

        $ok = $topic->load($topic->getId());

        $this->assertGreaterThanOrEqual(0, $ok, 'Topic load after save failed');

        $name = $topic->getFirstName(['type' => TopicMapInterface::SUBJECT_DEFAULT_NAME_TYPE]);

        $expected =
            [
                'value' => 'hello world',
                'datatype' => self::$topicMap->getTopicIdBySubject(DataTypeUtils::DATATYPE_STRING),
                'language' => 'en',
                'reifier' => false,
                'scope' => false
            ];

        $nameData = $name->getAll();

        $this->assertArraySubset($expected, $nameData);

        $topic->delete();
    }


    public function testDataType()
    {
        $topic = self::$topicMap->newTopic();

        $name = $topic->newName();
        $name->setType(TopicMapInterface::SUBJECT_DEFAULT_NAME_TYPE);
        $name->setValue('<p>hello world</p>');
        $name->setDataType(DataTypeUtils::DATATYPE_XHTML);

        $ok = $topic->save();

        $this->assertGreaterThanOrEqual(0, $ok, 'Topic save failed');

        $ok = $topic->load($topic->getId());

        $this->assertGreaterThanOrEqual(0, $ok, 'Topic load after save failed');

        $name = $topic->getFirstName(['type' => TopicMapInterface::SUBJECT_DEFAULT_NAME_TYPE]);

        $expected =
            [
                'value' => '<p>hello world</p>',
                'datatype' => self::$topicMap->getTopicIdBySubject(DataTypeUtils::DATATYPE_XHTML),
                'reifier' => false,
                'scope' => false
            ];

        $nameData = $name->getAll();

        $this->assertArraySubset($expected, $nameData);

        $topic->delete();
    }
}
