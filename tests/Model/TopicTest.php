<?php

use PHPUnit\Framework\TestCase;
use TopicCards\Interfaces\TopicMapInterface;


class TopicTest extends TestCase
{
    /** @var TopicMapInterface */
    protected static $topicmap;

    
    public static function setUpBeforeClass()
    {
        global $topicmap;
        
        self::$topicmap = $topicmap;
    }
    
    
    public function testCleanGetAll()
    {
        $topic = self::$topicmap->newTopic();

        $expected = 
            [
                'created' => false,
                'id' => '',
                'names' => [ ],
                'occurrences' => [ ],
                'reifies_id' => '',
                'reifies_what' => '',
                'subject_identifiers' => [ ],
                'subject_locators' => [ ],
                'types' => [ ],
                'updated' => false,
                'version' => 0
            ];
        
        $this->assertEquals($expected, $topic->getAll());
    }


    public function testCreateBlank()
    {
        $topic = self::$topicmap->newTopic();
        
        $ok = $topic->save();
        
        $this->assertGreaterThanOrEqual(0, $ok, 'Topic save failed');
        
        $ok = $topic->load($topic->getId());

        $this->assertGreaterThanOrEqual(0, $ok, 'Topic load after save failed');

        $expected =
            [
                'names' => [ ],
                'occurrences' => [ ],
                'reifies_id' => '',
                'reifies_what' => '',
                'subject_identifiers' => [ ],
                'subject_locators' => [ ],
                'types' => [ ],
                'version' => 1
            ];

        $all = $topic->getAll();
        
        $this->assertArraySubset($expected, $all);
        
        $this->assertRegExp
        (
            '/^[a-z0-9]{8}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{12}$/', 
            $all[ 'id' ], 
            'ID is not a UUID'
        );
        
        $this->assertRegExp
        (
            '/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}/', 
            $all[ 'created' ],
            'Created is not a timestamp'
        );
        
        $this->assertRegExp
        (
            '/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}/', 
            $all[ 'updated' ], 
            'Updated is not a timestamp'
        );
    }
}
