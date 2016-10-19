<?php

namespace TopicCards\Interfaces;


interface iPersistent extends iCore
{
    public function load($id);
    public function save();
    public function delete();
    public function getCreated();
    public function getUpdated();
    public function getVersion();
    public function getPreviousData();
}
