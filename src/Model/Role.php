<?php

namespace TopicCards\Model;

use TopicCards\Db\RoleDbAdapter;
use TopicCards\Interfaces\RoleInterface;
use TopicCards\Interfaces\RoleDbAdapterInterface;
use TopicCards\Interfaces\TopicMapInterface;
use TopicCards\Interfaces\TypedInterface;


class Role extends Core implements RoleInterface
{
    // TODO use , RoleDbAdapter
    use Reified, Typed;

    protected $player = false;
    
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


    public function getPlayerId()
    {
        return $this->player;
    }


    public function setPlayerId($topicId)
    {
        $this->player = $topicId;

        return 1;
    }


    public function getPlayer()
    {
        return $this->topicMap->getTopicSubject($this->getPlayerId());
    }


    public function setPlayer($topicSubject)
    {
        $topicId = $this->topicMap->getTopicIdBySubject($topicSubject, true);

        if (strlen($topicId) === 0) {
            return -1;
        }

        return $this->setPlayerId($topicId);
    }


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


    public function setAll(array $data)
    {
        $data = array_merge(
            [
                'player' => false
            ], $data);

        $ok = $this->setPlayerId($data['player']);

        if ($ok >= 0) {
            $ok = $this->setAllId($data);
        }

        if ($ok >= 0) {
            $ok = $this->setAllTyped($data);
        }

        if ($ok >= 0) {
            $ok = $this->setAllReified($data);
        }

        return $ok;
    }


    /**
     * Mark an existing (saved) role for removal on association save
     */
    public function remove()
    {
        $this->setPlayerId('');
    }


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
