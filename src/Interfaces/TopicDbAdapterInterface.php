<?php

namespace TopicCards\Interfaces;


interface TopicDbAdapterInterface extends PersistentDbAdapterInterface
{
    /**
     * @return object
     */
    public function selectReifiedObject();
}
