<?php

namespace TopicCards\Interfaces;


interface NameInterface extends CoreInterface, ReifiedInterface, ScopedInterface, TypedInterface
{
    public function getValue();
    public function setValue($str);
}
