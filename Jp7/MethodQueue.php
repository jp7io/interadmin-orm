<?php

namespace Jp7;

/**
 * Store methods, but dont run them yet.
 **/
class MethodQueue
{
    protected $stack = [];

    public function __call($method, $arguments)
    {
        $this->stack[] = [
            'method' => $method,
            'arguments' => $arguments,
        ];
    }

    public function _runOn($target)
    {
        foreach ($this->stack as $item) {
            call_user_func_array([$target, $item['method']], $item['arguments']);
        }
    }
}
