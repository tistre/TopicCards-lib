<?php

namespace TopicCards\Interfaces;


/**
 * Association role
 *
 * This interface defines the methods needed by a role in an association.
 */

interface RoleInterface extends CoreInterface, ReifiedInterface, TypedInterface
{
    /**
     * Get the player topic's ID
     *
     * @return string Topic ID
     */

    public function getPlayerId();


    /**
     * Set the player topic by its ID
     *
     * @param string $topicId Topic ID
     * @return int Zero or greater on success
     */

    public function setPlayerId($topicId);


    /**
     * Get the player topic's subject
     *
     * @return string Topic subject
     */

    public function getPlayer();


    /**
     * Set the player topic by its subject
     *
     * @param string $topicSubject Topic subject
     * @return int Zero or greater on success
     */

    public function setPlayer($topicSubject);
}
