<?php

namespace TopicCards\Interfaces;


/**
 * Association role
 *
 * This interface defines the methods needed by a role in an association.
 */
 
interface iRole extends iCore, iReified, iTyped
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
     * @param string $topic_id Topic ID
     * @return int Zero or greater on success
     */
     
    public function setPlayerId($topic_id);
    
    
    /**
     * Get the player topic's subject
     *
     * @return string Topic subject
     */
     
    public function getPlayer();
    
    
    /**
     * Set the player topic by its subject
     *
     * @param string $topic_subject Topic subject
     * @return int Zero or greater on success
     */
     
    public function setPlayer($topic_subject);
}
