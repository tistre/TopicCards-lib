<?php

namespace TopicCards\Interfaces;


interface TopicDbAdapterInterface extends PersistentDbAdapterInterface 
{
    public function selectReifiedObject();
}
