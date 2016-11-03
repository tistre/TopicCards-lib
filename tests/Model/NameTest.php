<?php

use PHPUnit\Framework\TestCase;
use TopicCards\Interfaces\TopicMapInterface;


class NameTest extends TestCase
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
        $occurrence_type = 'http://example.com/schema/newOccurrenceType';
        $datatype = 'http://example.com/schema/newDatatype';
        
        $topic = self::$topicmap->newTopic();
        
        $name = $topic->newName();
        $name->setType(TopicMapInterface::SUBJECT_DEFAULT_NAME_TYPE);
        $name->setValue('hello world');
        
        $ok = $topic->save();
        
        $this->assertGreaterThanOrEqual(0, $ok, 'Topic save failed');
        
        $ok = $topic->load($topic->getId());

        $this->assertGreaterThanOrEqual(0, $ok, 'Topic load after save failed');
        
        $name = $topic->getFirstName([ 'type' => TopicMapInterface::SUBJECT_DEFAULT_NAME_TYPE ]);
        
        $expected =
            [
                'value' => 'hello world',
                'reifier' => false,
                'scope' => false
            ];

        $name_data = $name->getAll();
        
        $this->assertArraySubset($expected, $name_data);
        
        $name_type_topic = self::$topicmap->newTopic();
        $ok = $name_type_topic->load($name->getTypeId());
        $this->assertGreaterThanOrEqual(0, $ok, 'Name type topic load failed');
        
        $this->assertContains
        (
            TopicMapInterface::SUBJECT_TOPIC_NAME_TYPE, 
            $name_type_topic->getTypes(), 
            'New name type topic has not been marked as name type.'
        );

        $topic->delete();
    }
}
