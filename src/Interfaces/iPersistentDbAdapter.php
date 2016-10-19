<?php

namespace TopicCards\Interfaces;


interface iPersistentDbAdapter
{
    public function selectAll(array $filters);
    public function insertAll(array $data);
    public function updateAll(array $data);
    public function deleteById($id, $version);
}
