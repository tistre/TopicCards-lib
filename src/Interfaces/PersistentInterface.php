<?php

namespace TopicCards\Interfaces;


interface PersistentInterface extends CoreInterface
{
    /**
     * @return string
     */
    public function getIdentifier();


    /**
     * @param string $id
     * @return bool
     */
    public function load($id);


    /**
     * @return void
     */
    public function save();


    /**
     * @return void
     */
    public function delete();


    /**
     * @return string ISO datetime
     */
    public function getCreated();


    /**
     * @param string $date ISO datetime
     * @return self
     */
    public function setCreated($date);


    /**
     * @return string ISO datetime
     */
    public function getUpdated();


    /**
     * @param string $date ISO datetime
     * @return self
     */
    public function setUpdated($date);


    /**
     * @return int
     */
    public function getVersion();


    /**
     * @param int $version
     * @return self
     */
    public function setVersion($version);


    /**
     * @return bool
     */
    public function isLoaded();


    /**
     * @return array
     */
    public function getPreviousData();
}
