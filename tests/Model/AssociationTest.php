<?php

use PHPUnit\Framework\TestCase;
use TopicCards\Exception\TopicCardsException;
use TopicCards\Interfaces\TopicMapInterface;
use TopicCards\Interfaces\TypedInterface;


class AssociationTest extends TestCase
{
    const ASSOCIATION_TYPE = 'http://example.com/schema/associationType' . __FILE__;
    const ROLE_TYPE = 'http://example.com/schema/roleType' . __FILE__;
    const TOPIC_A = 'http://example.com/topic/a' . __FILE__;
    const TOPIC_B = 'http://example.com/topic/b' . __FILE__;
    
    /** @var TopicMapInterface */
    protected static $topicmap;

    
    public static function setUpBeforeClass()
    {
        global $topicmap;
        
        self::$topicmap = $topicmap;
    }


    protected function setUp()
    {
        self::$topicmap->clearCache();
        
        $topic_a = self::$topicmap->newTopic();
        $topic_a->setSubjectIdentifiers([ self::TOPIC_A ]);
        $topic_a->save();

        $topic_b = self::$topicmap->newTopic();
        $topic_b->setSubjectIdentifiers([ self::TOPIC_B ]);
        $topic_b->save();
    }


    protected function tearDown()
    {
        $subjects = 
            [
                self::TOPIC_A,
                self::TOPIC_B,
                self::ASSOCIATION_TYPE,
                self::ROLE_TYPE
            ];
        
        foreach ($subjects as $subject)
        {
            $topic = self::$topicmap->newTopic();
            $ok = $topic->loadBySubject($subject);
            
            if ($ok < 0)
            {
                continue;
            }

            $association_ids = self::$topicmap->getAssociationIds([ 'role_player_id' => $topic->getId() ]);

            foreach ($association_ids as $association_id) 
            {
                $association = self::$topicmap->newAssociation();
                $association->load($association_id);
                $ok = $association->delete();
            }
            
            $ok = $topic->delete();
        }
    }
    
    
    public function testTypeRequired()
    {
        $association = self::$topicmap->newAssociation();
        
        $role_a = $association->newRole();
        $role_a->setPlayer(self::TOPIC_A);
        
        $role_b = $association->newRole();
        $role_b->setPlayer(self::TOPIC_B);

        $this->expectException(TopicCardsException::class);
        $this->expectExceptionCode(TypedInterface::ERR_TYPE_MISSING);
        
        $association->save();
    }


    public function testRoleTypeRequired()
    {
        $association = self::$topicmap->newAssociation();
        $association->setType(self::ASSOCIATION_TYPE);

        $role_a = $association->newRole();
        $role_a->setPlayer(self::TOPIC_A);

        $role_b = $association->newRole();
        $role_b->setPlayer(self::TOPIC_B);

        $this->expectException(TopicCardsException::class);
        $this->expectExceptionCode(TypedInterface::ERR_TYPE_MISSING);

        $association->save();
    }

    
    public function testRoleTypePresent()
    {
        $association = self::$topicmap->newAssociation();
        $association->setType(self::ASSOCIATION_TYPE);

        $role_a = $association->newRole();
        $role_a->setPlayer(self::TOPIC_A);
        $role_a->setType(self::ROLE_TYPE);

        $role_b = $association->newRole();
        $role_b->setPlayer(self::TOPIC_B);
        $role_b->setType(self::ROLE_TYPE);

        $ok = $association->save();
        $this->assertGreaterThanOrEqual(0, $ok, 'Association save failed');

        $ok = $association->delete();
        $this->assertGreaterThanOrEqual(0, $ok, 'Association delete failed');
    }
}
