<?php

namespace TopicCards\Interfaces;


interface PersistentSearchAdapterInterface
{
    /**
     * @return string
     */
    public function getSearchType();


    /**
     * @return int
     */
    public function index();


    /**
     * @return int
     */
    public function removeFromIndex();


    /**
     * @param array $params
     * @return array|bool
     */
    public function getIndexedData();


    /**
     * @return void
     */
    public function resetIndexRelated();


    /**
     * @param mixed $add
     * @return int
     */
    public function addIndexRelated($add);


    /**
     * @return int
     */
    public function indexRelated();
}
