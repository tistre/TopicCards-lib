<?php

namespace TopicCards\Interfaces;


interface PersistentInterface extends CoreInterface
{
    public function load($id);
    public function save();
    public function delete();
    public function getCreated();
    public function getUpdated();
    public function getVersion();
    
    /**
     * @return bool
     */
    public function isLoaded();
    
    public function getPreviousData();
}
