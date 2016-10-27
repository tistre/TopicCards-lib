<?php

use PHPUnit\Framework\TestCase;
use TopicCards\Interfaces\iTopicMap;
use TopicCards\Interfaces\iTyped;


class AssociationTest extends TestCase
{
    /** @var iTopicMap */
    protected static $topicmap;

    
    public static function setUpBeforeClass()
    {
        global $topicmap;
        
        self::$topicmap = $topicmap;
    }
    
    
    public function testTypeValidation()
    {
        $association_type = 'http://example.com/schema/newAssociationType';
        $association_type_id = self::$topicmap->getTopicIdBySubject($association_type, true);

        $role_type = 'http://example.com/schema/newOccurrenceType';
        $role_type_id = self::$topicmap->getTopicIdBySubject($role_type, true);
        
        $topic_a = self::$topicmap->newTopic();
        $ok = $topic_a->save();
        $this->assertGreaterThanOrEqual(0, $ok, 'Topic A save failed');

        $topic_b = self::$topicmap->newTopic();
        $ok = $topic_b->save();
        $this->assertGreaterThanOrEqual(0, $ok, 'Topic B save failed');
        
        $association = self::$topicmap->newAssociation();
        
        $role_a = $association->newRole();
        $role_a->setPlayerId($topic_a->getId());
        
        $role_b = $association->newRole();
        $role_b->setPlayerId($topic_b->getId());
        
        $ok = $association->save();
        $this->assertEquals(iTyped::ERR_TYPE_MISSING, $ok, 'Association saved although association type is missing');

        $association->setTypeId($association_type_id);

        $ok = $association->save();
        $this->assertEquals(iTyped::ERR_TYPE_MISSING, $ok, 'Association saved although role type is missing');

        $role_a->setTypeId($role_type_id);
        $role_b->setTypeId($role_type_id);
        
        $ok = $association->save();
        $this->assertGreaterThanOrEqual(0, $ok, 'Association save failed');

        $ok = $association->delete();
        $this->assertGreaterThanOrEqual(0, $ok, 'Association delete failed');

        $ok = $topic_a->delete();
        $this->assertGreaterThanOrEqual(0, $ok, 'Topic A delete failed');

        $ok = $topic_b->delete();
        $this->assertGreaterThanOrEqual(0, $ok, 'Topic B delete failed');

        $association_type_topic = self::$topicmap->newTopic();
        $ok = $association_type_topic->load($association_type_id);
        $this->assertGreaterThanOrEqual(0, $ok, 'Association type topic load failed');
        $ok = $association_type_topic->delete();
        $this->assertGreaterThanOrEqual(0, $ok, 'Association type topic delete failed');

        $role_type_topic = self::$topicmap->newTopic();
        $ok = $role_type_topic->load($role_type_id);
        $this->assertGreaterThanOrEqual(0, $ok, 'Role type topic load failed');
        $ok = $role_type_topic->delete();
        $this->assertGreaterThanOrEqual(0, $ok, 'Role type topic delete failed');
    }
}
