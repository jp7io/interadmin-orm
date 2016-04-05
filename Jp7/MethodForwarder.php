<?php

namespace Jp7;

class MethodForwarder
{
    protected $target;

    public function __construct($target)
    {
        $this->target = $target;
    }

    public function __call($method, $arguments)
    {
        return call_user_func_array([$this->target, $method], $arguments);
    }
}
