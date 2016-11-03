<?php

namespace TopicCards\Interfaces;


interface PersistentSearchAdapterInterface
{
    public function getSearchType();

    public function index();
    
    public function removeFromIndex();
    
    public function getIndexedData();
    
    public function resetIndexRelated();

    public function addIndexRelated($add);

    public function indexRelated();
}
