<?php

namespace TopicCards\Model;

use TopicCards\Db\RoleDbAdapter;
use TopicCards\Exception\TopicCardsLogicException;
use TopicCards\Interfaces\RoleInterface;
use TopicCards\Interfaces\RoleDbAdapterInterface;
use TopicCards\Interfaces\TopicMapInterface;
use TopicCards\Interfaces\TypedInterface;


class Role extends Core implements RoleInterface
{
    use ReifiedTrait, TypedTrait;

    /** @var string */
    protected $player = '';
    
    /** @var RoleDbAdapterInterface */
    protected $dbAdapter;


    /**
     * Name constructor.
     *
     * @param TopicMapInterface $topicMap
     */
    public function __construct(TopicMapInterface $topicMap)
    {
        parent::__construct($topicMap);

        $this->dbAdapter = new RoleDbAdapter($this);
    }


    /**
     * @return RoleDbAdapterInterface
     */
    public function getDbAdapter()
    {
        return $this->dbAdapter;
    }


    /**
     * Get the player topic's ID
     *
     * @return string Topic ID
     */
    public function getPlayerId()
    {
        return $this->player;
    }


    /**
     * Set the player topic by its ID
     *
     * @param string $topicId Topic ID
     * @return self
     */
    public function setPlayerId($topicId)
    {
        $this->player = $topicId;

        return $this;
    }


    /**
     * Get the player topic's subject
     *
     * @return string Topic subject
     */
    public function getPlayer()
    {
        return $this->topicMap->getTopicSubject($this->getPlayerId());
    }


    /**
     * Set the player topic by its subject
     *
     * @param string $topicSubject Topic subject
     * @return self
     */
    public function setPlayer($topicSubject)
    {
        $topicId = $this->topicMap->getTopicIdBySubject($topicSubject, true);

        if (strlen($topicId) === 0) {
            throw new TopicCardsLogicException
            (
                sprintf
                (
                    '%s: Player topic with subject <%s> not found.',
                    __METHOD__, $topicSubject
                )
            );
        }

        $this->setPlayerId($topicId);
        
        return $this;
    }


    /**
     * @return array
     */
    public function getAll()
    {
        $result =
            [
                'player' => $this->getPlayerId()
            ];

        $result = array_merge($result, $this->getAllId());

        $result = array_merge($result, $this->getAllTyped());

        $result = array_merge($result, $this->getAllReified());

        return $result;
    }


    /**
     * @param array $data
     * @return self
     */
    public function setAll(array $data)
    {
        $data = array_merge(
            [
                'player' => false
            ], $data);

        $this->setPlayerId($data['player']);
        $this->setAllId($data);
        $this->setAllTyped($data);
        $this->setAllReified($data);

        return $this;
    }


    /**
     * Mark an existing (saved) role for removal on association save
     * 
     * @return void
     */
    public function remove()
    {
        $this->setPlayerId('');
    }


    /**
     * @param string $msgHtml
     * @return int
     */
    public function validate(&$msgHtml)
    {
        $result = 1;

        if (strlen($this->getTypeId()) === 0) {
            $result = TypedInterface::ERR_TYPE_MISSING;
            $msgHtml .= 'Missing role type.';
        }

        return $result;
    }
}
