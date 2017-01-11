<?php

use PHPUnit\Framework\TestCase;
use TopicCards\Interfaces\TopicInterface;
use TopicCards\Interfaces\TopicMapInterface;


class RoleReifiedTest extends TestCase
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
        // Create types

        $association_type = 'http://example.com/schema/associationType' . __FILE__;
        $association_type_id = self::$topicmap->getTopicIdBySubject($association_type, true);

        $role_type = 'http://example.com/schema/roleType' . __FILE__;
        $role_type_id = self::$topicmap->getTopicIdBySubject($role_type, true);

        // Create topics

        $topic_a = self::$topicmap->newTopic();
        $ok = $topic_a->save();
        $this->assertGreaterThanOrEqual(0, $ok, 'Topic A save failed');

        $topic_b = self::$topicmap->newTopic();
        $ok = $topic_b->save();
        $this->assertGreaterThanOrEqual(0, $ok, 'Topic B save failed');

        // Create association

        $association = self::$topicmap->newAssociation();
        $association->setTypeId($association_type_id);

        $role_a = $association->newRole();
        $role_a->setTypeId($role_type_id);
        $role_a->setPlayerId($topic_a->getId());

        $role_b = $association->newRole();
        $role_b->setTypeId($role_type_id);
        $role_b->setPlayerId($topic_b->getId());

        $ok = $association->save();
        $this->assertGreaterThanOrEqual(0, $ok, 'Association save failed');

        // Reify

        $reifier_topic = $role_a->newReifierTopic();
        $ok = $reifier_topic->save();
        $this->assertGreaterThanOrEqual(0, $ok, 'Reifier save failed');

        $ok = $association->save();
        $this->assertGreaterThanOrEqual(0, $ok, 'Association save failed');

        // Reload

        $ok = $reifier_topic->load($reifier_topic->getId());
        $this->assertGreaterThanOrEqual(0, $ok, 'Reifier load after save failed');

        $ok = $association->load($association->getId());
        $this->assertGreaterThanOrEqual(0, $ok, 'Association load after save failed');

        // Test

        $this->assertEquals($role_a->getId(), $reifier_topic->getReifiesId(), 'Reifier topic ID is not the role ID');
        $this->assertEquals(TopicInterface::REIFIES_ROLE, $reifier_topic->getReifiesWhat());

        $this->assertEquals($reifier_topic->getId(), $role_a->getReifierId(),
            'Role reifier ID is not the reifier topic ID');

        $reified_object = $reifier_topic->getReifiedObject();
        $this->assertEquals($role_a->getId(), $reified_object['role']->getId(),
            'Reified role object ID is not the role ID');
        $this->assertEquals($association->getId(), $reified_object['association']->getId(),
            'Reified association object ID is not the association ID');

        // Cleanup

        $ok = $reifier_topic->delete();
        $this->assertGreaterThanOrEqual(0, $ok, 'Reifier topic delete failed');

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
