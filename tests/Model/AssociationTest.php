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
    protected static $topicMap;


    public static function setUpBeforeClass()
    {
        global $topicMap;

        self::$topicMap = $topicMap;
    }


    protected function setUp()
    {
        self::$topicMap->clearCache();

        $topicA = self::$topicMap->newTopic();
        $topicA->setSubjectIdentifiers([self::TOPIC_A]);
        $topicA->save();

        $topicB = self::$topicMap->newTopic();
        $topicB->setSubjectIdentifiers([self::TOPIC_B]);
        $topicB->save();
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

        foreach ($subjects as $subject) {
            $topic = self::$topicMap->newTopic();
            $loaded = $topic->loadBySubject($subject);

            if (! $loaded) {
                continue;
            }

            $associationIds = self::$topicMap->getAssociationIds(['role_player_id' => $topic->getId()]);

            foreach ($associationIds as $associationId) {
                $association = self::$topicMap->newAssociation();
                $association->load($associationId);
                $association->delete();
            }

            $topic->delete();
        }
    }


    public function testTypeRequired()
    {
        $association = self::$topicMap->newAssociation();

        $roleA = $association->newRole();
        $roleA->setPlayer(self::TOPIC_A);

        $roleB = $association->newRole();
        $roleB->setPlayer(self::TOPIC_B);

        $this->expectException(TopicCardsException::class);
        $this->expectExceptionCode(TypedInterface::ERR_TYPE_MISSING);

        $association->save();
    }


    public function testRoleTypeRequired()
    {
        $association = self::$topicMap->newAssociation();
        $association->setType(self::ASSOCIATION_TYPE);

        $roleA = $association->newRole();
        $roleA->setPlayer(self::TOPIC_A);

        $roleB = $association->newRole();
        $roleB->setPlayer(self::TOPIC_B);

        $this->expectException(TopicCardsException::class);
        $this->expectExceptionCode(TypedInterface::ERR_TYPE_MISSING);

        $association->save();
    }


    public function testRoleTypePresent()
    {
        $association = self::$topicMap->newAssociation();
        $association->setType(self::ASSOCIATION_TYPE);

        $roleA = $association->newRole();
        $roleA->setPlayer(self::TOPIC_A);
        $roleA->setType(self::ROLE_TYPE);

        $roleB = $association->newRole();
        $roleB->setPlayer(self::TOPIC_B);
        $roleB->setType(self::ROLE_TYPE);

        $ok = $association->save();
        $this->assertGreaterThanOrEqual(0, $ok, 'Association save failed');

        $ok = $association->delete();
        $this->assertGreaterThanOrEqual(0, $ok, 'Association delete failed');
    }
}
