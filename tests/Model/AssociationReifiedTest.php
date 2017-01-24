<?php

use PHPUnit\Framework\TestCase;
use TopicCards\Interfaces\TopicInterface;
use TopicCards\Interfaces\TopicMapInterface;


class AssociationReifiedTest extends TestCase
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
        // Create types

        $associationType = 'http://example.com/schema/associationType' . __FILE__;
        $associationTypeId = self::$topicMap->getTopicIdBySubject($associationType, true);

        $roleType = 'http://example.com/schema/roleType' . __FILE__;
        $roleTypeId = self::$topicMap->getTopicIdBySubject($roleType, true);

        // Create topics

        $topicA = self::$topicMap->newTopic();
        $ok = $topicA->save();
        $this->assertGreaterThanOrEqual(0, $ok, 'Topic A save failed');

        $topicB = self::$topicMap->newTopic();
        $ok = $topicB->save();
        $this->assertGreaterThanOrEqual(0, $ok, 'Topic B save failed');

        // Create association

        $association = self::$topicMap->newAssociation();
        $association->setTypeId($associationTypeId);

        $roleA = $association->newRole();
        $roleA->setTypeId($roleTypeId);
        $roleA->setPlayerId($topicA->getId());

        $roleB = $association->newRole();
        $roleB->setTypeId($roleTypeId);
        $roleB->setPlayerId($topicB->getId());

        $ok = $association->save();
        $this->assertGreaterThanOrEqual(0, $ok, 'Association save failed');

        // Reify

        $reifierTopic = $association->newReifierTopic();
        $ok = $reifierTopic->save();
        $this->assertGreaterThanOrEqual(0, $ok, 'Reifier save failed');

        $ok = $association->save();
        $this->assertGreaterThanOrEqual(0, $ok, 'Association save failed');

        // Reload

        $ok = $reifierTopic->load($reifierTopic->getId());
        $this->assertGreaterThanOrEqual(0, $ok, 'Reifier load after save failed');

        $ok = $association->load($association->getId());
        $this->assertGreaterThanOrEqual(0, $ok, 'Association load after save failed');

        // Test

        $this->assertEquals($association->getId(), $reifierTopic->getReifiesId(),
            'Reifier topic ID is not the association ID');
        
        $this->assertEquals(TopicInterface::REIFIES_ASSOCIATION, $reifierTopic->getReifiesWhat());

        $this->assertEquals($reifierTopic->getId(), $association->getReifierId(),
            'Association reifier ID is not the reifier topic ID');

        $reifiedObject = $reifierTopic->getReifiedObject();
        
        $this->assertEquals($association->getId(), $reifiedObject['association']->getId(),
            'Reified association object ID is not the association ID');

        // Cleanup

        $ok = $reifierTopic->delete();
        $this->assertGreaterThanOrEqual(0, $ok, 'Reifier topic delete failed');

        $ok = $association->delete();
        $this->assertGreaterThanOrEqual(0, $ok, 'Association delete failed');

        $ok = $topicA->delete();
        $this->assertGreaterThanOrEqual(0, $ok, 'Topic A delete failed');

        $ok = $topicB->delete();
        $this->assertGreaterThanOrEqual(0, $ok, 'Topic B delete failed');

        $associationTypeTopic = self::$topicMap->newTopic();
        $ok = $associationTypeTopic->load($associationTypeId);
        $this->assertGreaterThanOrEqual(0, $ok, 'Association type topic load failed');
        $ok = $associationTypeTopic->delete();
        $this->assertGreaterThanOrEqual(0, $ok, 'Association type topic delete failed');

        $roleTypeTopic = self::$topicMap->newTopic();
        $ok = $roleTypeTopic->load($roleTypeId);
        $this->assertGreaterThanOrEqual(0, $ok, 'Role type topic load failed');
        $ok = $roleTypeTopic->delete();
        $this->assertGreaterThanOrEqual(0, $ok, 'Role type topic delete failed');
    }
}
