<?php

namespace TopicCards\Interfaces;


/**
 * Reified element
 *
 * Names, occurrences, associations and roles can be reified, i.e. linked to a
 * topic that contains additional data. This interface defines the methods for
 * handling reification.
 */

interface ReifiedInterface
{
    /**
     * Get the reifier topic's ID
     *
     * @return string Topic ID
     */
    public function getReifierId();


    /**
     * Set the reifier topic by its ID
     *
     * @param string $topic_id
     * @return int Zero or greater on success
     */
    public function setReifierId($topic_id);


    /**
     * Create a new reifier topic
     *
     * Creates a new topic and sets it as the reifier. Note that the topic is
     * not saved yet: You have to set its property and then call save().
     * Also make sure to save this topic as well!
     *
     * @return TopicInterface New topic
     */
    public function newReifierTopic();
}
