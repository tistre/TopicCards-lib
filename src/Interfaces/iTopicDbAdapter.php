<?php

namespace TopicCards\Interfaces;


interface iTopicDbAdapter extends iPersistentDbAdapter 
{
    public function selectReifiedObject();
}
