<?php

namespace TopicCards\Interfaces;


interface PersistentDbAdapterInterface
{
    public function selectAll(array $filters);
    public function insertAll(array $data);
    public function updateAll(array $data);
    public function deleteById($id, $version);
}
