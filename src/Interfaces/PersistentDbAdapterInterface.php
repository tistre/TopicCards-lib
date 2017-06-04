<?php

namespace TopicCards\Interfaces;


interface PersistentDbAdapterInterface
{
    /**
     * @param array $filters
     * @return array
     */
    public function selectAll(array $filters);


    /**
     * @param array $data
     * @return void
     */
    public function insertAll(array $data);


    /**
     * @param array $data
     * @return void
     */
    public function updateAll(array $data);


    /**
     * @param string $id
     * @param int $version
     * @return void
     */
    public function deleteById($id, $version);
}
